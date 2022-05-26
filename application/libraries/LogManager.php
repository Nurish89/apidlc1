<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
date_default_timezone_set('Asia/Ho_Chi_Minh');
class LogManager {

	public function logDEBUG($apiName, $input, $output)
	{
		$sub_folder_name	= date("Y-m");
		$today_date 		= date("Y-m-d");
		$today_datetime		= date("Y-m-d H:i:s");

		$storage_path 		= FCPATH.'application/logs/DEBUGLOG/';
		$sub_folder_path 	= $storage_path.$sub_folder_name.'/';

		$file_name = $today_date.'.txt';
		$file_path = $sub_folder_path.$file_name;

		if(!file_exists($storage_path)) {
			if(!mkdir($storage_path, 0777)) {
				echo 'Can\'t create folder at'.$storage_path;
				return FALSE;
			}
		}

		if(!file_exists($sub_folder_path)) {
			if(!mkdir($sub_folder_path, 0777)) {
				echo 'Can\'t create folder at'.$storage_path;
				return FALSE;
			}
		}

		if(!file_exists($file_path)) {
			if(!file_put_contents($file_path, "\n")) {
				echo 'Can\'t create file at '.$sub_folder_path;
				return FALSE;
			}
		}

		$fp = fopen($file_path, 'at');
		fwrite($fp, $today_datetime." : ".$apiName.'|'.json_encode($input).'|'.json_encode($output));
		fclose($fp);

	}

	public function logApi($apiName, $input, $output)
	{
		$sub_folder_name	= date("Y-m");
		$today_date 		= date("Y-m-d");
		$today_datetime		= date("Y-m-d H:i:s");

		$storage_path 		= FCPATH.'application/logs/APILOG/';
		$sub_folder_path 	= $storage_path.$sub_folder_name.'/';

		$file_name = $today_date.'.txt';
		$file_path = $sub_folder_path.$file_name;

		if(!file_exists($storage_path)) {
			if(!mkdir($storage_path, 0777)) {
				echo 'Can\'t create folder at'.$storage_path;
				return FALSE;
			}
		}

		if(!file_exists($sub_folder_path)) {
			if(!mkdir($sub_folder_path, 0777)) {
				echo 'Can\'t create folder at'.$storage_path;
				return FALSE;
			}
		}

		if(!file_exists($file_path)) {
			if(!file_put_contents($file_path, "\n")) {
				echo 'Can\'t create file at '.$sub_folder_path;
				return FALSE;
			}
		}

		$fp = fopen($file_path, 'at');

		$fp = fopen($file_path, 'at');
		fwrite($fp, $today_datetime." : ".$apiName.'|'.json_encode($input).'|'.json_encode($output));
		fclose($fp);

		fwrite($fp, "\n\n");
		fclose($fp);

	}
}