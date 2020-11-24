<?php

namespace Docty\Demos;

class LoanCalculator
{
    




    function __construct(array $loan = [])
    {
    	$this->loan = $loan;
    }



    public function getInterestPerMonth()
    {
        $interestPerMonth = [];
        foreach ($this->loan $value) {
            $interestPerMonth[] = $value->interestRate*($value->principal+$value->penalty);
        }
        return $interestPerMonth;
    }
}