<?php

namespace HarvestData\Command;

use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Input\InputInterface;

class ComparePeriods extends HarvestDataCommand {

	protected function configure() {
		$this
		->setName('HarvestData:ComparePeriods')
		->setAliases(array('compare', 'ComparePeriods'))
		->setDescription('Compare data between two periods');
		
		$this->setChartTypes(array("numberstat"));
		$this->setChartPeriods(array(null));	
			
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->loadConfig($input);
		
	  $from_date      = $this->getHarvestFromDate($input, "Ymd","yesterday");
	  $to_date        = $this->getHarvestToDate($input, "Ymd", "yesterday");
    $chartType      = $this->getChartType($input, "numberstat");
    $chartPeriod    = $this->getChartPeriod($input, null);
    
    if(!$outputFilename = $input->getOption("output-file")) {
      $outputFilename = 'ComparePeriods-'.$from_date.'-'.$to_date.'.xml';
    }
    
    $output->writeln('ComparePeriods executed: ' . date('Ymd H:i:s'));
 		$output->writeln('Output filename: ' . $outputFilename);
// 		$output->writeln(sprintf('Chart type is "%s" and period is "%s"',$chartType,$chartPeriod));

    $datetime1 = new \DateTime($to_date);
    $datetime2 = new \DateTime($from_date);
    $interval = $datetime1->diff($datetime2);


    //echo "Datetime1: (to) " .  $datetime1->format('Ymd') . "\n";
    //echo "Datetime2: (from) " .  $datetime2->format('Ymd') . "\n";    
    //echo "Diff: ". $interval->format('%R%a days') . "\n";

    // subtract one day as all days are inclusive
    $prev_from_date = $datetime2->sub(new \DateInterval('P1D'))->format('Ymd');
    $prev_to_date   = $datetime2->sub($interval)->format('Ymd');
    
    //echo "new to date: " . $prev_to_date  . "\n";
    //echo "new from date: " . $prev_from_date  . "\n";

    switch ($chartType) {
      case 'numberstat':
      
		    $output->writeln(sprintf("Collecting Harvest entries between %s to %s",$from_date,$to_date));      
        $currentPeriodEntries = $this->fetchBillableHoursInPeriod($from_date, $to_date);

        $output->writeln("\n");
		    $output->writeln(sprintf("Collecting Harvest entries between %s to %s",$prev_from_date,$prev_to_date));
        $prevPeriodEntries = $this->fetchBillableHoursInPeriod($prev_from_date, $prev_to_date);

        //print_r($currentPeriodEntries);
        //print_r($prevPeriodEntries);
        
        // prepare the response!
        $geckoresponse = new \GeckoResponse();
        $data['type'] = "standard";
        $data['item'][] = array('value' => $currentPeriodEntries["statistics"]["totalhours"], 'text' => 'hours');
        $data['item'][] = array('value' => $prevPeriodEntries["statistics"]["totalhours"], 'text' => '');   
        $data = $geckoresponse->getResponse($data, true);        

      break;
      
      default:
        $output->writeln("ComparePeriods ChartType not recognized -> ".$chartType."");
        return;
      break;
    }

    // lets write the data to a file
    if($data) {
      $outputFile = new StreamOutput(fopen('data/'.$outputFilename, 'w', false));
      $outputFile->doWrite($data, false);
    	$output->writeln("\nComparePeriods completed -> ".$outputFilename." updated");      
    }

  }
}