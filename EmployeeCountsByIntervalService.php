<?php

namespace App\Services;

use App\Models\FormApplication;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\DB;

class EmployeeCountsByIntervalService
{
    public function getCountsByInterval($startDate, $endDate, $interval = 'month')
    {
        switch ($interval) {
            case 'week':
                $dateFormat = "%yW%v";
                $intervalSpec = 'P1W';
                $periodFormat = "y\WW";
                break;
            case 'month':
                $dateFormat = "%y%b";
                $intervalSpec = 'P1M';
                $periodFormat = "yM";
                break;
            case 'year':
                $dateFormat = "%Y";
                $intervalSpec = 'P1Y';
                $periodFormat = "Y";
                break;
            default:
                abort(400, 'Invalid interval value');
        }

        // Retrieve data for "exit_employee" form
        $exitQuery = FormApplication::select(
            DB::raw("
                CAST(JSON_EXTRACT(data, '$._business_unit') AS UNSIGNED) AS buId,
                DATE_FORMAT(created_at, '" . $dateFormat . "') AS date,
                COUNT(*) as count
            ")
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('form_id', 4)
            ->groupBy('buId', 'date')
            ->orderBy('date', 'ASC');

        $exitResult = [
            'byBu' => [],
            'total' => 0,
        ];

        // Loop through each form application and populate the result array for "exit_employee"
        foreach ($exitQuery->get() as $application) {
            $buId = $application->buId;
            $date = $application->date;
            $count = $application->count;

            // Find the index of the business unit in the result array
            $index = array_search($buId, array_column($exitResult['byBu'], 'buId'));

            // If the business unit is not yet in the result array, add it with a numeric index
            if ($index === false) {
                $exitResult['byBu'][] = [
                    'buId' => $buId,
                    'buTotal' => 0,
                    'count' => [],
                ];
                $index = count($exitResult['byBu']) - 1;
            }

            // Add the count to the appropriate date and update the total count
            if (!isset($exitResult['byBu'][$index]['count'][$date])) {
                $exitResult['byBu'][$index]['count'][$date] = 0;
            }
            $exitResult['byBu'][$index]['count'][$date] += $count;
            $exitResult['byBu'][$index]['buTotal'] += $count;
            $exitResult['total'] += $count;
        }

        // Add missing weeks/months/years with count 0 for "exit_employee"
        $intervalObj = new DateInterval($intervalSpec);
        $period = new DatePeriod(new DateTime($startDate), $intervalObj, new DateTime($endDate));
        $intervalArray = [];
        foreach ($period as $date) {
            $dateStr = $date->format($periodFormat);
            array_unshift($intervalArray, $dateStr);
            foreach ($exitResult['byBu'] as $index => $buData) {
                if (!isset($exitResult['byBu'][$index]['count'][$dateStr])) {
                    $exitResult['byBu'][$index]['count'][$dateStr] = 0;
                };
            }
        }

        // Retrieve data for "new_employee" form 新進人員
        $newQuery = FormApplication::select(
            DB::raw("
                CAST(JSON_EXTRACT(data, '$.business_unit') AS UNSIGNED) AS buId,
                DATE_FORMAT(created_at, '" . $dateFormat . "') AS date,
                COUNT(*) as count
            ")
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('form_id', 1)
            ->groupBy('buId', 'date')
            ->orderBy('date', 'ASC');

        $newResult = [
            'byBu' => [],
            'total' => 0,
        ];

        // Loop through each form application and populate the result array for "new_employee"
        foreach ($newQuery->get() as $application) {
            $buId = $application->buId;
            $date = $application->date;
            $count = $application->count;

            // Find the index of the business unit in the result array
            $index = array_search($buId, array_column($newResult['byBu'], 'buId'));

            // If the business unit is not yet in the result array, add it with a numeric index
            if ($index === false) {
                $newResult['byBu'][] = [
                    'buId' => $buId,
                    'buTotal' => 0,
                    'count' => [],
                ];
                $index = count($newResult['byBu']) - 1;
            }

            // Add the count to the appropriate date and update the total count
            if (!isset($newResult['byBu'][$index]['count'][$date])) {
                $newResult['byBu'][$index]['count'][$date] = 0;
            }
            $newResult['byBu'][$index]['count'][$date] += $count;
            $newResult['byBu'][$index]['buTotal'] += $count;
            $newResult['total'] += $count;
        }

        // Add missing weeks/months/years with count 0 for "new_employee"
        foreach ($intervalArray as $dateStr) {
            foreach ($newResult['byBu'] as $index => $buData) {
                if (!isset($newResult['byBu'][$index]['count'][$dateStr])) {
                    $newResult['byBu'][$index]['count'][$dateStr] = 0;
                };
            }
        }

        return [
            'exitEmployee' => $exitResult,
            'newEmployee' => $newResult,
            'intervalArray' => $intervalArray
        ];
    }
}
