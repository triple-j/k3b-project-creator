#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

use trejeraos\K3bProject;


$folder = realpath($argv[1]);


$project = new K3bProject(__DIR__ . "/k3b_project_files/maindata.xml");
$project->createProject($folder);

#echo $project->getDOMDocument()->saveXML();

$project->saveProject();

