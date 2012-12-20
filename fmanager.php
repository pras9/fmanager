<?php

    /**
     * a simple file manager app for the web
     * 
     * @author pras <pras.svo@gmail.com>
     * @version 1.0
     */
    
    // --- app config ---
    global $config, $var;
    $config = array();
    $var = array();
    $config['store_path'] = './';
    /* $config['authenticate'] = false;
    $config['user'] = 'lserver';
    $config['password'] = '34545'; */
    $config['full_store_path'] = str_replace('fmanager.php', '', __FILE__).str_replace('./', '', $config['store_path']);
    
    date_default_timezone_set(@date_default_timezone_get());
    
    // --- utility functions ---
    function formatBytes($b,$p = null) {
        $units = array("B","kB","MB","GB","TB","PB","EB","ZB","YB");
        $c=0;
        if(!$p && $p !== 0) {
            foreach($units as $k => $u) {
                if(($b / pow(1024,$k)) >= 1) {
                    $r["bytes"] = $b / pow(1024,$k);
                    $r["units"] = $u;
                    $c++;
                }
            }
            return number_format($r["bytes"],2) . " " . $r["units"];
        } else {
            return number_format($b / pow(1024,$p)) . " " . $units[$p];
        }
    }
    
    // --- functions to operate on files as read from $_GET ---
    function cmd_list_files() {
        global $config, $var;
        //var_dump(is_dir($config['store_path'])); exit;
        if(!is_dir($config['store_path'])) {
            mkdir($config['store_path'], 0777)
              or die("Cannot create directory <strong>{$config['store_path']}</strong>: check for permission!");
        }
        session_start();
        echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
        <head profile="http://purl.org/NET/erdf/profile">
            <title>Local File Manager</title>
            <style type="text/css">
                body{font-family:Arial;}
                thead, tfoot{background-color:#E1E1E1;text-decoration:italic;}
                td{border:solid 1px #B2B2B2;padding:2px 10px;};
            </style>
        </head>
        <body>
        <form action="'.basename(__FILE__).'?cmd=upload_file" method="post" enctype="multipart/form-data">
            <input type="file" name="file[]" multiple="multiple" />
            <input type="submit" name="submit" value="Upload" />
        </form>
        <table>
        <thead>
            <tr>
                <td>S/n</td>
                <td>Filename</td>
                <td>Date</td>
                <td>Size</td>
                <td>Actions</td>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td>S/n</td>
                <td>Filename</td>
                <td>Date</td>
                <td>Size</td>
                <td>Actions</td>
            </tr>
        </tfoot>
        <tbody>';
        $id = 0;
        if($dh = opendir($config['store_path'])) {
            while(($file = readdir($dh)) !== false) {
                if(($file != '.') && ($file != '..') && ($file != basename($_SERVER['PHP_SELF']))) {
                    echo '<tr>
                        <td>'.(++$id).'</td>
                        <td>'.$file.'</td>
                        <td>'.date("M d, Y H:i:s.", filemtime($config['full_store_path'].$file)).'</td>
                        <td>'.formatBytes(filesize($config['full_store_path'].$file)).'</td>
                        <td><a href="fmanager.php?cmd=delete_file&file='.$file.'">Delete</a>&nbsp;&nbsp;&nbsp;<a href="fmanager.php?cmd=download&file='.$file.'">Download</a></td>
                    </tr>';
                }
            }
            closedir($dh);
        }
        if($id == 0)
            echo '<tr><td colspan="5" align="center"><font size="+2" color="#cccccc">No Files</font></td></tr>';
        echo '</tbody>
            </table>
        </body>
        </html>';
    }
    function cmd_upload_file() {
        global $config, $var;
        ini_set("MAX_EXECUTION_TIME", 3600*4);
        
        if(is_array($_FILES['file']['size'])) {
            foreach($_FILES['file']['size'] as $k => $v) {
                if($v > 0) {
                    @move_uploaded_file($_FILES['file']['tmp_name'][$k], $config['store_path'].$_FILES['file']['name'][$k]);
                    chmod($config['store_path'].$_FILES['file']['name'][$k], 0777);
                }
            }
        } else {
            if($_FILES['file']['size'] > 0) {
                @move_uploaded_file($_FILES['file']['tmp_name'], $config['store_path'].$_FILES['file']['name']);
                chmod($config['store_path'].$_FILES['file']['name'], 0777);
            }
        }
        header("Location: ".basename(__FILE__)."?cmd=list_files");
    }
    function cmd_delete_file() {
        global $config, $var;
        if(isset($_GET['file'])) {
            unlink($config['store_path'].$_GET['file']);
        }
        header("Location: ".basename(__FILE__)."?cmd=list_files");
    }
    function cmd_rename_file() {
        global $config, $var;
        header("Location: ".basename(__FILE__)."?cmd=list_files");
    }
    function cmd_download() {
        global $config, $var;
        if(isset($_GET['file'])) {
            $filename = $_GET['file'];
            $file = $config['full_store_path'].$_GET['file'];
            // var_dump($file); exit();
            $filearr = explode('.', $filename);
            $file_extension = array_pop($filearr);
        
            // required for IE, otherwise Content-disposition is ignored
            if(ini_get('zlib.output_compression'))
                ini_set('zlib.output_compression', 'Off');
        
            if($file == "") {
                header("Location: ".basename(__FILE__));
            } elseif (!file_exists($file)) {
                header("Location: ".basename(__FILE__));
            }
            switch($file_extension) {
                case "pdf": $ctype="application/pdf"; break;
                case "exe": $ctype="application/octet-stream"; break;
                case "zip": $ctype="application/zip"; break;
                case "doc": $ctype="application/msword"; break;
                case "xls": $ctype="application/vnd.ms-excel"; break;
                case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
                case "gif": $ctype="image/gif"; break;
                case "png": $ctype="image/png"; break;
                case "jpeg":
                case "jpg": $ctype="image/jpg"; break;
                default: $ctype="application/force-download";
            }
            header("Pragma: public"); // required
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private",false); // required for certain browsers 
            header("Content-Type: $ctype");
            header("Content-Disposition: attachment; filename=\"".basename($file)."\";" );
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: ".filesize($file));
            readfile("$file");
            exit();
        } else {
          return false;
        }
    }
    
    
    $cmd = isset($_GET['cmd'])?$_GET['cmd']:false;
    if($cmd) {
        if(function_exists('cmd_'.$cmd))
            call_user_func('cmd_'.$cmd);
        else
            cmd_list_files();
    } else {
        cmd_list_files();
    }
    
    
    session_write_close();

?>
