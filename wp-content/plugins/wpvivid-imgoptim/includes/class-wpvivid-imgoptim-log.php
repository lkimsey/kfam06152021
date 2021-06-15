<?php

if (!defined('WPVIVID_IMGOPTIM_DIR'))
{
    die;
}

class WPvivid_Image_Optimize_Log
{
    public $log_file;
    public $log_file_handle;

    public function __construct()
    {
        $this->log_file_handle=false;
    }

    public function CreateLogFile($file_name='')
    {
        if(empty($file_name))
        {
            $offset=get_option('gmt_offset');
            $localtime = time() + $offset * 60 * 60;
            $file_name='wpvivid_imgoptim_'.date('Ymd',$localtime).'_log.txt';
        }

        $this->log_file=$this->GetSaveLogFolder().$file_name;

        $this->log_file_handle = fopen($this->log_file, 'a');
        $text="====================================================\n";
        $time =date("Y-m-d H:i:s",time());
        $text.='open log file: '.$time."\n";
        fwrite($this->log_file_handle,$text);
        return $this->log_file;
    }

    public function OpenLogFile($file_name='')
    {
        if(empty($file_name))
        {
            $offset=get_option('gmt_offset');
            $localtime = time() + $offset * 60 * 60;
            $file_name='wpvivid_imgoptim_'.date('Ymd',$localtime).'_log.txt';
        }

        $this->log_file=$this->GetSaveLogFolder().$file_name;

        $this->log_file_handle = fopen($this->log_file, 'a');

        return $this->log_file;
    }

    public function WriteLog($log,$type)
    {
        if ($this->log_file_handle)
        {
            $time =date("Y-m-d H:i:s",time());
            $text='['.$time.']'.'['.$type.']'.$log."\n";
            fwrite($this->log_file_handle,$text );
        }
    }

    public function GetlastLog()
    {
        if(empty($file_name))
        {
            $offset=get_option('gmt_offset');
            $localtime = time() + $offset * 60 * 60;
            $file_name='wpvivid_imgoptim_'.date('Ymd',$localtime).'_log.txt';
        }

        $this->log_file=$this->GetSaveLogFolder().$file_name;
        $file = file($this->log_file);
        $text='';
        for ($i = max(0, count($file)-1); $i < count($file); $i++)
        {
            $text.= $file[$i] . "\n";
        }
        return $text;
    }

    public function CloseFile()
    {
        if ($this->log_file_handle)
        {
            fclose($this->log_file_handle);
            $this->log_file_handle=false;
        }
    }

    public function GetSaveLogFolder()
    {
        $options=get_option('wpvivid_optimization_options',array());

        $backup_path=isset($options['backup_path'])?$options['backup_path']:WPVIVID_IMGOPTIM_DEFAULT_SAVE_DIR;

        $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_path.DIRECTORY_SEPARATOR.'log';

        if(!is_dir($path))
        {
            @mkdir($path,0777,true);
            @fopen($path.DIRECTORY_SEPARATOR.'index.html', 'x');
            $tempfile=@fopen($path.DIRECTORY_SEPARATOR.'.htaccess', 'x');
            if($tempfile)
            {
                $text="deny from all";
                fwrite($tempfile,$text );
            }
        }

        return $path.DIRECTORY_SEPARATOR;
    }

    public function Copy_To_Error()
    {
        $dir=dirname( $this->log_file);
        $file=basename( $this->log_file);
        if(!is_dir($dir.DIRECTORY_SEPARATOR.'error'))
        {
            @mkdir($dir.DIRECTORY_SEPARATOR.'error',0777,true);
            @fopen($dir.DIRECTORY_SEPARATOR.'error'.'/index.html', 'x');
            $tempfile=@fopen($dir.DIRECTORY_SEPARATOR.'error'.'/.htaccess', 'x');
            if($tempfile)
            {
                $text="deny from all";
                fwrite($tempfile,$text );
                @fclose($tempfile);
            }
        }

        if(!file_exists( $this->log_file))
        {
            return ;
        }

        if(file_exists($dir.DIRECTORY_SEPARATOR.'error'.DIRECTORY_SEPARATOR.$file))
        {
            @unlink($dir.DIRECTORY_SEPARATOR.'error'.DIRECTORY_SEPARATOR.$file);
        }

        @copy( $this->log_file,$dir.DIRECTORY_SEPARATOR.'error'.DIRECTORY_SEPARATOR.$file);
    }
}