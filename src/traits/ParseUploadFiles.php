<?php

namespace wuwuseo\adapter\workerman\traits;

trait ParseUploadFiles
{
    /**
     * Parse $_FILES.
     *
     * @param string $http_body
     * @param string $http_post_boundary
     *
     * @return void
     */
    protected static function parseUploadFiles($http_body, $http_post_boundary)
    {
        $http_body = \substr($http_body, 0, \strlen($http_body) - (\strlen($http_post_boundary) + 4));
        $boundary_data_array = \explode($http_post_boundary . "\r\n", $http_body);
        if ($boundary_data_array[0] === '') {
            unset($boundary_data_array[0]);
        }
        $key = -1;
        foreach ($boundary_data_array as $boundary_data_buffer) {
            list($boundary_header_buffer, $boundary_value) = \explode("\r\n\r\n", $boundary_data_buffer, 2);
            // Remove \r\n from the end of buffer.
            $boundary_value = \substr($boundary_value, 0, -2);
            $key++;
            foreach (\explode("\r\n", $boundary_header_buffer) as $item) {
                list($header_key, $header_value) = \explode(": ", $item);
                $header_key = \strtolower($header_key);
                \var_dump($header_key.'--'.$header_value);
                switch ($header_key) {
                    case "content-disposition":
                        if(preg_match('/name="(.*?)"; filename="(.*?)"$/', $header_value, $match)){// Is file data.
                            if (strpos($match[1],'[]')) {
                                $isFiles = true;
                            }else{
                                $isFiles = false;
                            }
                            $fileFormName = str_replace('[]','',$match[1]);
                            $isPostField = false;
                        } else { // Is post field.
                            $isPostField = true;
                        }
                        if ($isPostField) {
                            // Parse $_POST.
                            if (\preg_match('/name="(.*?)"$/', $header_value, $match)) {
                                $_POST[$match[1]] = $boundary_value;
                            }
                        } else {
                            $error = 0;
                            $tmp_file = '';
                            $size = \strlen($boundary_value);
                            $tmp_upload_dir = self::uploadTmpDir();
                            if (!$tmp_upload_dir) {
                                $error = UPLOAD_ERR_NO_TMP_DIR;
                            } else {
                                $tmp_file = \tempnam($tmp_upload_dir, 'workerman.upload.');
                                if ($tmp_file === false || false == \file_put_contents($tmp_file, $boundary_value)) {
                                    $error = UPLOAD_ERR_CANT_WRITE;
                                }
                            }
                            if (!isset($_FILES[$match[1]])) {
                                $_FILES[$fileFormName] = array();
                            }
                            // Parse upload files.
                            if($isFiles){
                                $_FILES[$fileFormName][$key] = array(
                                    'key'      => $match[1],
                                    'name'     => $match[2],
                                    'tmp_name' => $tmp_file,
                                    'size'     => $size,
                                    'error'    => $error,
                                    'type'     => ''
                                );
                            } else {
                                $_FILES[$fileFormName] += array(
                                    'key'      => $match[1],
                                    'name'     => $match[2],
                                    'tmp_name' => $tmp_file,
                                    'size'     => $size,
                                    'error'    => $error,
                                    'type'     => ''
                                );
                            }

                        }
                        break;
                    case "content-type":
                        if(isset($fileFormName)){
                            if($isFiles){
                                $_FILES[$fileFormName][$key]['type'] = \trim($header_value);
                            } else {
                                $_FILES[$fileFormName]['type'] = \trim($header_value);
                            }
                        }
                        break;
                }
            }
        }
    }
}
