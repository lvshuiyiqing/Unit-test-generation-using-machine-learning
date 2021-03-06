<?php

$folders = array_values(array_filter(glob('tech-projects/*'), 'is_dir'));
$scriptInstance = $argv[1];
$totalScriptInstances = $argv[2];

for($i = 0; $i < count($folders); $i++) {
	if($i % $totalScriptInstances != $scriptInstance)
		continue;
	$folder = $folders[$i];
	$tagHuntFile="$folder/tagHunt.txt";

	if(!file_exists($folder . "/report.txt")) {
		continue;
	}
	
	if(file_exists($tagHuntFile)) {
		continue;
	}
	
	$report = file_get_contents("$folder/report.txt");
	if(strpos($report, "Tests run:") !== false)
		continue;
	
	if(strpos($report, "BUILD FAILURE") === false)
		continue;
	
	
	$tags = explode("\n",execute($folder, "git for-each-ref --sort=taggerdate --format '%(refname)' refs/tags | tac"));
	$counter = 0;
	for($conter = 0; $counter < count($tags) && $counter < 3; $counter++) {
		$tag = $tags[$counter];
		
		echo "$folder - $tag\n";
		echo "Checkout\n";
		execute($folder, "git checkout \"$tag\"");
		echo "Test\n";
		execute($folder, "mvn test --fae -Dmaven.test.failure.ignore=true -Dlicense.skip=true -Dcheckstyle.skip > reportTmp.txt");
		echo "Test done\n";
		
		$fileTmp = file_get_contents("$folder/reportTmp.txt");
		if (strpos($fileTmp, "Tests run:") !== false) {
			echo "New report for $folder!\n";
			execute($folder, "cp reportTmp.txt report.txt");
			execute($folder, "rm addedToQueue.txt");
			file_put_contents("$folder/tag.txt", $tag);
			break;
		}
	}

	echo "Tag search done\n";

	file_put_contents($tagHuntFile, "done");
}

function execute($folder, $command) {
	return shell_exec("cd $folder && $command 2>&1");
}	
