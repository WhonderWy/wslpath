#!/usr/bin/php
<?php

// Auto-detects path - converts to a Unix path if it is a Windows path and vice-versa
// Correctly handles symlinks
// Correctly handles paths under lxss directory (hidden user directory under which Unix-only files and directories are located).
// Correctly handles :line:column suffix. For example, `subl $(wslpath /mnt/d/script.js:10:2)` would open D:\script.js at line 10, column 2 in Sublime Text

function usage() {
return 'wslpath [-m|-u|-w|-h] NAME[:line[:col]]

Output type options:

  -w           (default) prints Windows form of NAME (C:\WINNT)
  -m           like -w, but with regular slashes (C:/WINNT)
  -u           prints Unix form of NAME (/mnt/c/winnt)

Other options:

  -h           displays usage information

If no output type is selected, the program will try to detect the form of
NAME and print the opposite type (eg. will print Windows form for Unix
path).';
}

$lxssPath_ = null;
function lxssPath() {
	global $lxssPath_;
	if ($lxssPath_) return $lxssPath_;
	$lxssPath_ = trim(exec('cmd.exe /c "echo %LOCALAPPDATA%" 2> /dev/null')) . "\\lxss";
	return $lxssPath_;
}

function pathType($path) {
	preg_match('/^([A-Za-z]):(.*)$/', $path, $r);
	if (count($r) > 1) return 'win';
	if (strpos($path, '/') === 0) return 'unix';
	if (strpos($path, '~') === 0) return 'unix';
	if (strpos($path, "\\") !== false) return 'win';
	if (strpos($path, '/') !== false) return 'unix';
	return 'unix';
}

function slashJoin($part1, $part2, $slash) {
	$part1 = rtrim($part1, "/\\");
	$part2 = ltrim($part2, "/\\");
	return $part1 . $slash . $part2;
}

function setLastSlash($path, $addIt, $slash) {
	$path = rtrim($path, "/\\");
	if (!$addIt) return $path;
	return $path . $slash;
}

function toSlash($path, $slash) {
	$path = str_replace("\\", $slash, $path);
	$path = str_replace('/', $slash, $path);
	return $path;
}

function explodeLineColNumbers($path) {
	preg_match('/(.*):(\d+):(\d+)/', $path, $r);
	if (count($r) >= 4) {
		return array(
			'path' => $r[1],
			'line' => $r[2],
			'col' => $r[3],
		);
	}

	preg_match('/(.*):(\d+)/', $path, $r);
	if (count($r) >= 3) {
		return array(
			'path' => $r[1],
			'line' => $r[2],
		);
	}

	return array('path' => $path);
}

function pathToWin($inputPath) {
	$path = $inputPath[0] != '/' ? slashJoin(getcwd(), $inputPath, '/') : $inputPath;
	$path = realpath($path);
	if (!$path) return toSlash($inputPath, "\\");

	if (strpos($path, '/mnt/') === 0) {
		$initialDir = getcwd();

		$inputDir = dirname($path);

		$ok = @chdir($inputDir);
		if (!$ok) return toSlash($inputPath, "\\");

		$output = trim(exec('cmd.exe /c cd 2> /dev/null', $output));
		$output = slashJoin($output, basename($path), "\\");
	} else {
		$output = slashJoin(lxssPath(), $path, "\\");
	}

	$c = $inputPath[strlen($inputPath) - 1];
	$output = setLastSlash($output, $c == '/' || $c == "\\", "\\");

	return toSlash($output, "\\");
}

function pathToUnix($winPath) {
	preg_match('/([A-Za-z]):(.*)/', $winPath, $r);
	
	if (count($r) < 3) {
		$output = slashJoin(getcwd(), $winPath, '/');
	} else {
		$drive = '/mnt/' . strtolower($r[1]);
		$output = slashJoin($drive, $r[2], '/');
	}

	$c = $winPath[strlen($winPath) - 1];
	$output = setLastSlash($output, $c == '/' || $c == "\\", "\\");

	return toSlash($output, '/');
}

function main($args) {
	array_splice($args, 0, 1);

	$operation = null;
	$inputPath = null;
	$mixedMode = false;

	while (count($args)) {
		$arg = $args[0];

		if ($arg == '-h') {
			echo usage() . "\n";
			return;
		}

		if ($arg == '-w') {
			$operation = 'to_win';
			array_splice($args, 0, 1);
			continue;
		}

		if ($arg == '-u') {
			$operation = 'to_unix';
			array_splice($args, 0, 1);
			continue;
		}

		if ($arg == '-m') {
			$mixedMode = true;
			$operation = 'to_win';
			array_splice($args, 0, 1);
			continue;
		}

		if (strpos($arg, '-') !== 0) {
			$inputPath = trim($arg);
			break;
		}

		throw new Exception('Unknown option: ' . $arg);
	}

	if (!$inputPath) throw new Exception('No path provided');

	if (!$operation) {
		$operation = pathType($inputPath) == 'win' ? 'to_unix' : 'to_win';
	}

	$pathInfo = explodeLineColNumbers($inputPath);
	$outputPath = '';

	if ($operation == 'to_win') {
		$outputPath = pathToWin($pathInfo['path']);
		if ($mixedMode) $outputPath = toSlash($outputPath, '/');
	}

	if ($operation == 'to_unix') {
		$outputPath = pathToUnix($pathInfo['path']);
	}

	if (isset($pathInfo['line'])) $outputPath .= ':' . $pathInfo['line'];
	if (isset($pathInfo['col'])) $outputPath .= ':' . $pathInfo['col'];

	echo $outputPath . "\n";
}

try {
	main($argv);
} catch (Exception $e) {
	echo $e->getMessage() . "\n\n" . usage() . "\n";
	die(1);
}