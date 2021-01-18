<?php

namespace Docty\Demos;

use Carbon\Carbon;
use App\PaymentSchedule;
use App\IncomeManagement;
Use App\Customer;
use App\BankManagement;
use App\Pettycash;
use App\Expense;

class LoanCalculator
{
    




    function __construct($loan = [])
    {
        $this->loan = $loan;
    }



    public function getInterestPerMonth()
    {
        $interestPerMonth = [];
        foreach ($this->loan as  $value) {
            $interestPerMonth[] = $value->interestRate*($value->principal+$value->penalty);
        }
        return $interestPerMonth;
    }
 

     public  function getPrincipalPerMonth()
    {
        $principalPerMonth = [];
        foreach ($this->loan as  $value) {
            $principalPerMonth[] = ($value->principal+$value->penalty)/$value->months;
        }
        return $principalPerMonth;
    }
    
    public  function getNumberOfRepayment()
    {
        $numberOfRepayment = [];
        foreach ($this->loan as  $value) {
            if ($value->paymentType == 'Daily') 
                $numberOfRepayment[] = $value->months*20;
            elseif ($value->paymentType == 'Weekly') 
                $numberOfRepayment[] = $value->months*4;
            elseif ($value->paymentType == 'Monthly') 
                $numberOfRepayment[] = $value->months;
        }
        return $numberOfRepayment;
    }

    public  function getInterestPeriod()
    {
        $interestPeriod = [];
        foreach ($this->loan as  $value) {
            if ($value->paymentType == 'Daily') 
                $interestPeriod[] = 20;
            elseif ($value->paymentType == 'Weekly') 
                $interestPeriod[] = 4;
            elseif ($value->paymentType == 'Monthly') 
                $interestPeriod[] = 1;
        }
        return $interestPeriod;
    } 

    

    public  function getPrincipalToPay()
    {
        $numberOfRepayment = $this->getNumberOfRepayment();
        $principalToPay = [];
        foreach ($this->loan as $key => $value) {
            $principalToPay[$key] = ($value->principal+$value->penalty)/$numberOfRepayment[$key];
        }
        return $principalToPay;
    } 

    public  function getInterestToPay()
    {
        $numberOfRepayment = $this->getNumberOfRepayment();
        $interestToPay = [];
        foreach ($this->loan as $key =>  $value) {
            $interestToPay[] = (($value->principal+$value->penalty)*$value->interestRate*$value->months)/$numberOfRepayment[$key];
        }
        return $interestToPay;
    }

    public  function getAmountToPay( $principalToPay, $interestToPay)
    {
        $amountToPay = [];
        foreach ($this->loan as $key => $value) {
            $amountToPay[] = $principalToPay[$key]+$interestToPay[$key];
        }
        return $amountToPay;
    }
    
    public  function getCompletePaymentMade( $amountToPay)
    {
        $completePaymentMade = [];
        foreach ($this->loan as $key =>  $value) {
            $calculatePayment = 0;
            $paymentVariable = PaymentSchedule::where('loanId', $value->loanId)->get('paid');
            foreach ($paymentVariable as  $item) {
                $calculatePayment = $calculatePayment + $item->paid;
            }
            if($calculatePayment == 0) 
                $completePaymentMade[] = 0;
            else 
                $completePaymentMade[] = $calculatePayment/$amountToPay[$key];
            }
        return $completePaymentMade;
    }

    public  function getPaymentNumberDue( $completePaymentMade)
    {
        $numberofRepayment = $this->getNumberOfRepayment();
        $paymentNumberDue = [];
        foreach ($this->loan as $key => $value) {
            if ($numberofRepayment[$key] - $completePaymentMade[$key] == 0)
                $paymentNumberDue[] = $completePaymentMade[$key];
            elseif (intval($completePaymentMade[$key]) == $completePaymentMade[$key])
                $paymentNumberDue[] = $completePaymentMade[$key] + 1;
            else
                $paymentNumberDue[] = ceil($completePaymentMade[$key]);
            }
        return $paymentNumberDue;
    }
    
    public  function getNextPaymentDue( $completePaymentMade)
    {
        $nextPaymentDue  = [];
        foreach ($this->loan as $key => $value) {
            if ($value->paymentType == 'Weekly')    
                $step1= 7;
            elseif ($value->paymentType == 'Monthly')    
                $step1= 30;
            else
                $step1 = 1;

            $step2 = $step1*$completePaymentMade[$key];
            $starter= Carbon::parse($value->startDate)->format('m/d/Y');
            $nextPaymentDue[] =  Carbon::parse($starter)->addDays($step2)->format('m/d/Y');
        }
        
        return $nextPaymentDue;
    }
    
    public  function getPrincipalOutstanding()
    {
        $principalPaid = $this->getPrincipalPaid();
        $principalOutstanding  = [];
        foreach ($this->loan as $key => $value) {
            $principalOutstanding[] = ($value->principal+$value->penalty) - $principalPaid[$key];
        }
        return $principalOutstanding;
    } 

    public  function getNumberOfDaysOutstanding( $loanStatus, $nextPaymentDue)
    {
        $numberOfDaysOutstanding  = [];
        $today = Carbon::now()->format('m/d/Y');
        foreach ($this->loan as $key => $value) {
            if ($loanStatus == 'Paid')
                $numberOfDaysOutstanding[] =  0;
            elseif  (Carbon::parse($nextPaymentDue[$key])->diffInDays($today, false) < 0)  
                $numberOfDaysOutstanding[] =  0;
            else 
                $numberOfDaysOutstanding[] = Carbon::parse($nextPaymentDue[$key])->diffInDays($today, false);
        }
        
        return $numberOfDaysOutstanding;
    }

    public  function getPeiodInArrears( $numberOfDaysOutstanding)
    {
        $periodInArrears = [];
        foreach ($this->loan as $key => $value) {
            if ($value->paymentType == 'Daily')
                $periodInArrears[] = (5/7)*$numberOfDaysOutstanding[$key];
            elseif ($value->paymentType == 'Weekly')
                $periodInArrears[] = $numberOfDaysOutstanding[$key]/7;
            elseif ($value->paymentType == 'Monthly')
                $periodInArrears[] = $numberOfDaysOutstanding[$key]/28;
        }
        return $periodInArrears;
    }

    public  function getPeriodCovered()
    {
        $periodCovered = [];
        $today =  Carbon::now()->format('m/d/Y');
        $numberofRepayment = $this->getNumberOfRepayment();
        foreach ($this->loan as $key => $value) {
            if ($value->paymentType == 'Daily'){
                if(Carbon::parse($value->startDate)->diffInDays($today, false) >= $numberofRepayment[$key])
                    $periodCovered[] = $numberofRepayment[$key];
                elseif(Carbon::parse($value->startDate)->diffInDays($today, false) < 0)
                    $periodCovered[] = 0;
                else
                    $periodCovered[] = Carbon::parse($value->startDate)->diffInDays($today, false);
            }
            if ($value->paymentType == 'Weekly'){
                if(Carbon::parse($value->startDate)->diffInDays($today, false)+7/7 >= $numberofRepayment[$key])
                    $periodCovered[] = $numberofRepayment[$key];
                elseif(Carbon::parse($value->startDate)->diffInDays($today, false) < 0)
                    $periodCovered[] = 0;
                else
                    $periodCovered[] = (Carbon::parse($value->startDate)->diffInDays($today, false)+7)/7;
            }
            if ($value->paymentType == 'Monthly'){
                $startDate = Carbon::parse($value->startDate)->diffInDays($today, false);
                $answer = floor(($startDate+30)/30, );
                if($answer >= $numberofRepayment[$key])
                    $periodCovered[] =  $numberofRepayment[$key];
                elseif(Carbon::parse($value->startDate)->diffInDays($today, false) < 0)
                    $periodCovered[] = 0;
                else{
                    $periodCovered[] =   $answer;
                }
            }
        }
        return $periodCovered;
    }
    
    public  function getPrincipalToBePaidAsDate( $principalToPay, $periodCovered)
    {
        
        $principalToBePaidAsDate = [];
        foreach ($this->loan as $key => $value) {
            $principalToBePaidAsDate[$key] = $principalToPay[$key]*$periodCovered[$key];
        }
        return $principalToBePaidAsDate;
    }

    public  function getInterestToBePaidAsDate( $interestToPay, $periodCovered)
    {
        
        $interestToBePaidAsDate = [];
        foreach ($this->loan as $key => $value) {
            $interestToBePaidAsDate[] = $interestToPay[$key]*$periodCovered[$key];
        }
        return $interestToBePaidAsDate;
    }

    public  function getAmountToBePaidAsDate( $interestToBePaidAsDate, $principalToBePaidAsDate)
    {
        $amountToBePaidAsDate  = [];
        foreach ($this->loan as $key=> $value) {
            $amountToBePaidAsDate[] = $interestToBePaidAsDate[$key]+$principalToBePaidAsDate[$key];
        }
        return $amountToBePaidAsDate;
    }

    public  function getPaymentMade()
    {
        $paymentMade = [];
        foreach ($this->loan as  $value) {
            $calculatePayment = 0;
            $paymentVariable = PaymentSchedule::where('loanId', $value->loanId)->get('paid');
            foreach ($paymentVariable as  $item) {
                $calculatePayment = $calculatePayment + $item->paid;
            }
            
            $paymentMade[] = $calculatePayment;
        }
        return $paymentMade;
    }

    public  function getPrincipalPaid()
    {
        $interestPerMonth = $this->getInterestPerMonth();

        $principalPaid = [];
        foreach ($this->loan as $key => $value) {
            $calculatePayment = 0;
            $paymentVariable = PaymentSchedule::where('loanId', $value->loanId)->get('paid');
            foreach ($paymentVariable as   $item) {
                $calculatePayment  = $calculatePayment + $item->paid;
            }
            if($calculatePayment == 0) 
                $right = 0;
            else 
                $right = ($value->principal+$value->penalty)/(($value->months*$interestPerMonth[$key])+($value->principal+$value->penalty))*$calculatePayment; 
            
            if (($value->principal+$value->penalty) > round($right,1))
                $principalPaid[] = round($right,1);
            else  
                $principalPaid[] = ($value->principal + $value->penalty);
        }
        return $principalPaid;
    }

    public  function getInterestPaid(   $principalPaid)
    {
        $interestPaid = [];
        foreach ($this->loan as  $key => $value) {
            $calculatePayment = 0;
            $paymentVariable = PaymentSchedule::where('loanId', $value->loanId)->get('paid');
            foreach ($paymentVariable as $item) {
                $calculatePayment  = $calculatePayment + $item->paid;
            }
            if($calculatePayment == 0)
                $interestPaid[$key] = 0;    
            else
                $interestPaid[$key] = $calculatePayment - $principalPaid[$key];
        }
        return $interestPaid;
    }

    public  function getInterestRemains( $principalPaid)
    {
        $interestPeriod = $this->getInterestPeriod();
        $numberOfRepayment = $this->getNumberOfRepayment();
        $paymentMade =  $this->getPaymentMade();
        $interestRemains = [];
        foreach ($this->loan as $key => $value) {
            $interestRemainsCal = (((($value->principal+$value->penalty)*$value->interestRate)/$interestPeriod[$key])*$numberOfRepayment[$key])-($paymentMade[$key] - $principalPaid[$key]);
            if($interestRemainsCal < 0)
                $interestRemains[] = 0;
            else
                $interestRemains[] = $interestRemainsCal;
        }
        return $interestRemains;
    }
    
    public  function getTotalAmountRemains( $interestRemains, $principalOutstanding)
    {
        $totalAmountRemains = [];
        foreach ($this->loan as $key =>  $value) {
            $totalAmountRemains[] = $interestRemains[$key]+ $principalOutstanding[$key];
        }
        return $totalAmountRemains;
    }

    public  function getPrincipalInArrears($principalToBePaidAsDate, $principalPaid)
    {
        $principalInArrears = [];
        foreach ($this->loan as $key =>  $value) {
           if($principalToBePaidAsDate[$key] - $principalPaid[$key] < 0)
                $principalInArrears[] = 0;
            else
                $principalInArrears[] = $principalToBePaidAsDate[$key] - $principalPaid[$key];

        }
        return $principalInArrears;
    }

    public  function getInterestInArrears( $interestToBePaidAsDate, $interestPaid)
    {
        $interstInArrears[0] = 0;
        foreach ($this->loan as $key => $value) {
           if($interestToBePaidAsDate[$key] - $interestPaid[$key] < 0)
                $interstInArrears[$key] = 0;
            else
                $interstInArrears[$key] = $interestToBePaidAsDate[$key] - $interestPaid[$key];

        }
        return $interstInArrears;
    }

    
    #################### LOAN DUE #####################
    
    public  function getPrincipalDueToday( $nextPaymentDue, $principalInArrears)
    {
        $today =  Carbon::now()->format('m/d/Y');
        $principalDueToday = [];
        foreach ($this->loan as $key => $value) {
            if ($nextPaymentDue[$key] <= $today)
                $principalDueToday[] =  $principalInArrears[$key];
            else
                $principalDueToday[] = 0;
        }
        return $principalDueToday;
    }

    public  function getInterestDueToday( $nextPaymentDue, $interestInArrears)
    {
        $today =  Carbon::now()->format('m/d/Y');
        $interestDueToday = [];
        foreach ($this->loan as $key => $value) {
            if ($nextPaymentDue[$key] <= $today)
                $interestDueToday[] =  $interestInArrears[$key];
            else
                $interestDueToday[] = 0;
        }
        return $interestDueToday;
    }

      public  function getAmountDueToday( $nextPaymentDue, $amountToPay)
    {
        $today =  Carbon::now()->format('m/d/Y');
        $amountDueToday = [];
        foreach ($this->loan as $key => $value) {
            if ($nextPaymentDue[$key] == $today)
                $amountDueToday[] =  $amountToPay[$key];
            else
                $amountDueToday[] = 0;
        }
        return $amountDueToday;
    }

      public  function getLoanOutstandingDue( $nextPaymentDue, $totalAmountRemains)
    {
        $today =  Carbon::now()->format('m/d/Y');
        $loanOutstandingDue = [];
        foreach ($this->loan as $key=>$item){
            if ($nextPaymentDue[$key] == $today)
                $loanOutstandingDue[] =  $totalAmountRemains[$key];
            else
                $loanOutstandingDue[] = 0;
        }
        return $loanOutstandingDue;
    }

    public  function getBankDeposit()
    {
        $total = 0;
        $deposit = BankManagement::all('deposit');
        foreach ($deposit as $key => $value) {
            $total = $total + $value->deposit;
        }
        return $total;
    }

    public   function getBankCashAtHand()
    {
        $bank = 0;
        $variable = BankManagement::where('ledger', 'Cash At Hand')->orderBy('created_at', 'ASC')->get();
        foreach ($variable as $value) {
            $bank =  $bank + floatval($value->deposit);
        }
        return $bank;
    }

    public  function getBankWithdrawal()
    {
        $total = 0;
        $withdrawal = BankManagement::all('withdrawal');
        foreach ($withdrawal as $key => $value) {
            $total = $total + $value->withdrawal;
        }
        return $total;
    }
 
    public  function getPettycashDeposit()
    {
        $result = Expense::all('deposit');
        $totalDeposit = 0;
        
        foreach ($result as $value) {
            $totalDeposit = $totalDeposit + $value->deposit;
        }

        
        return $totalDeposit;
    }

    public  function getPettycashWithdrawal()
    {
        $result = Expense::all('amount');
        $totalAmount = 0;
        
        foreach ($result as $value) {
            $totalAmount = $totalAmount + $value->amount;
        }

        
        return $totalAmount;
    }


    public function sum($value=[])
    {
        $collection = collect($value)->sum();

        return $collection;
    }
}