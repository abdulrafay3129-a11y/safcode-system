<?php

function getEndDate($admission_date, $duration_months){
    return date('Y-m-d', strtotime("+$duration_months months", strtotime($admission_date)));
}

/* OPTIONAL (future use) */
function calculateStudentStatus($admission_date, $duration_months, $manual_status = null){

    if ($manual_status === 'dropout') return 'dropout';
    if ($manual_status === 'inactive') return 'inactive';
    if ($manual_status === 'certified') return 'certified';

    $end_date = getEndDate($admission_date, $duration_months);

    if (date('Y-m-d') >= $end_date){
        return 'completed';
    }

    return 'active';
}