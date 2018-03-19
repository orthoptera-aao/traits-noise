<?php

function noise_info() {
  return(
    array(
      "noise" => array(
        "dependencies" => array("bioacoustica") //BioAcoustica provides wave file.
      )
    )
  );
}

function noise_init() {
  $init = array(
    "R" => array(
      "type" => "cmd",
      "required" => "required",
      "missing text" => "noise requires R.",
      "version flag" => "--version"
    ),
    "seewave" => array( 
      "type" => "Rpackage",
      "required" => "required",
      "missing text" => "noise requires the R seewave package.",
      "version flag" => "--quiet -e 'packageVersion(\"seewave\")'",
      "version line" => 1
    ),
    "inflection" => array( 
      "type" => "Rpackage",
      "required" => "required",
      "missing text" => "noise requires the R inflection package.",
      "version flag" => "--quiet -e 'packageVersion(\"inflection\")'",
      "version line" => 1
    )
  );
  return ($init);
}

function noise_prepare() {
  global $system;
  core_log("info", "noise", "Attempting to list noise files on analysis server.");
  exec("s3cmd ls s3://bioacoustica-analysis/noise/".$system["modules"]["noise"]["git_hash"]."/", $output, $return_value);
  if ($return_value == 0) {
    if (count($output) == 0) {
      $system["analyses"]["noise"] = array();
    } else {
      foreach ($output as $line) {
        $start = strrpos($line, "/");
        $system["analyses"]["noise"][] = substr($line, $start + 1);
      }
    }
  core_log("info", "noise", count($system["analyses"]["noise"])." noise analysis files found.");
  }
  return(array());
}

function noise_analyse($recording) {
  global $system;
  $return = array();
  if (!in_array($recording["id"].".csv", $system["analyses"]["noise"])) {
    $file = core_download("wav/".$recording["id"].".wav");
    if ($file == NULL) {
      core_log("warning", "noise", "File was not available, skipping analysis.");
      return($return);
    }
    $return[$recording["id"].".wav"] = array(
      "file name" => $recording["id"].".wav",
      "local path" => "scratch/wav/",
      "save path" => NULL
    );
    core_log("info", "noise", "Attepting to analyse noise for recording ".$recording["id"].".");
    exec("Rscript modules/traits-noise/noise/noise.R ".$recording["id"]." scratch/wav/".$recording["id"].".wav", $output, $return_value);
    if ($return_value == 0) {
      $return[$recording["id"].".hist.png"] = array(
        "file name" => $recording["id"].".hist.png",
        "local path" => "modules/traits-noise/noise/",
        "save path" => "noise/".$system["modules"]["noise"]["git_hash"]."/"
      );
      $return[$recording["id"].".spectro.png"] = array(
        "file name" => $recording["id"].".spectro.png",
        "local path" => "modules/traits-noise/noise/",
        "save path" => "noise/".$system["modules"]["noise"]["git_hash"]."/"
      );
      $return[$recording["id"].".csv"] = array(
        "file name" => $recording["id"].".csv",
        "local path" => "modules/traits-noise/noise/",
        "save path" => "noise/".$system["modules"]["noise"]["git_hash"]."/"
      );
    } else {
      core_log("warning", "noise", "Recording ".$recording["id"].": Issue analysing noise ".serialize($output));
    }
  }
  return($return);
}
