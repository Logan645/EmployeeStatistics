<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\DateValidation;
use App\Http\Controllers\Controller;
use App\Services\EmployeeCountsByIntervalService;


// 加入時間篩選，及資料分隔區間
class EmployeeStatisticsController extends Controller
{
    protected $employeeCountsByIntervalService;

    public function __construct(EmployeeCountsByIntervalService $employeeCountsByIntervalService)
    {
        $this->employeeCountsByIntervalService = $employeeCountsByIntervalService;
    }

    public function __invoke(Request $request)
    {
        [$startDate, $endDate] = DateValidation::validateAndConvert($request);
        $interval = $request->input('interval');
        $formApplicationCount = $this->employeeCountsByIntervalService->getCountsByInterval($startDate, $endDate, $interval);
        $response = [
            'data' => 
                $formApplicationCount
            ,
            'meta' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'interval' => $interval,
            ],
        ];
        return response()->json($response);
    }
}
