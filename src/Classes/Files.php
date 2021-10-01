<?php
/**
 * This is script handles files
 * @author Phelix Juma <jumaphelix@Kuza\Krypton.co.ke>
 * @copyright (c) 2018, Kuza Lab
 * @package Kuza\Krypton
 */

namespace Kuza\Krypton\Classes;


use Kreait\Firebase\Exception\RuntimeException;

/**
 * Class for managing files
 * @package Kuza\Krypton
 */
class Files {

    /**
     * @var S3
     */
    protected $S3;

    private $s3AccessKey;
    private $s3AccesSecret;
    private $s3Version;
    private $s3Region;
    private $s3bucketName;

    /**
     * mime types for images
     * @var array
     */
    private $images_mime_types = [
        // images
        'png' => 'image/png',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp'
    ];

    /**
     * mime types for zip archive file
     * @var array
     */
    private $zip_mime_types = [
        // archives
        'zip' => 'application/zip'
    ];

    /**
     * mime types for video
     * @var array
     */
    private $video_mime_types = [
        // video
        'webm' => 'video/webm',
        '3gp' => 'video/3gpp',
        'mp4' => 'video/mp4',
        'flv'=>'video/x-flv'
    ];

    /**
     * mime types for pdf
     * @var array
     */
    private $pdf_mime_types = [
        // adobe
        'pdf' => 'application/pdf'
    ];

    /**
     * mime types for word documents
     * @var array
     */
    private $word_mime_types = [
        // ms office
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'csv' => 'text/csv'
    ];

    function __construct( S3 $S3) {
        $this->S3 = $S3;
    }

    /**
     * Init S3
     *
     * @param $s3AccessKey
     * @param $s3AccessSecret
     * @param $bucketName
     * @param $s3Version
     * @param $s3Region
     * @return $this
     */
    public function initS3($s3AccessKey, $s3AccessSecret, $bucketName, $s3Version, $s3Region) {

        $this->s3AccessKey = $s3AccessKey;
        $this->s3AccesSecret = $s3AccessSecret;
        $this->s3Version = $s3Version;
        $this->s3Region = $s3Region;
        $this->s3bucketName = $bucketName;

        return $this;
    }

    /**
     * This function gets information about the file
     * @param string $file The file whose details are to be retrieved
     * @return array Returns the name,size,tmp,extension,contentType and info as a string of the file
     */
    public function getFileInfo($file) {

        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        $stringInfo = $finfo->file($file['tmp_name']);

        $extension = "";
        $mime_type = "";
        $contentType = "";
        $fileName = Utils::escape(explode(".",$file['name'])[0]);
        $name = (new Randomstring(20))->uniqueString;

        $allowed_mime_types = array_merge($this->images_mime_types,$this->video_mime_types,$this->pdf_mime_types,$this->zip_mime_types,$this->word_mime_types);

        //get the file extension.
        foreach ($allowed_mime_types as $key => $value) {
            if (strpos($stringInfo, $value) !== false) {
                $extension = $key;
                $mime_type = $value;
                $contentType = $value;
                break;
            }
        }

        $fileInfo = array(
            "name" => $name,
            "file_name" => $fileName,
            "size" => $file['size'],
            "tmp" => $file['tmp_name'],
            "stringInfo" => $stringInfo, //contains the entire file info string
            "extension" => $extension,
            "mime_type" => $mime_type,
            "content-type" => $contentType
        );
        return $fileInfo;
    }

    /**
     * Check if the file is an image or not
     * @param $fileInfo
     * @return bool
     */
    private function isImage($fileInfo) {

        $fileType = false;

        foreach($this->images_mime_types as $ext => $mime) {
            if($ext == $fileInfo['extension'] && $fileInfo['mime_type'] == $mime) {
                $fileType = "image";
                break;
            }
        }
        return $fileType;
    }

    /**
     * Check if the file is a video or not
     * @param $fileInfo
     * @return bool
     */
    private function isVideo($fileInfo) {

        $fileType = false;

        foreach($this->video_mime_types as $ext => $mime) {
            if($ext == $fileInfo['extension'] && $fileInfo['mime_type'] == $mime) {
                $fileType = "video";
                break;
            }
        }
        return $fileType;
    }

    /**
     * Check if the file is a pdf or not
     * @param $fileInfo
     * @return bool
     */
    private function isPDF($fileInfo) {
        $fileType = false;

        if($fileInfo['extension'] == "pdf" && $fileInfo['mime_type'] == "application/pdf") {
            $fileType = "PDF";
        }
        return $fileType;
    }

    /**
     * Check if the file is a zip file or not
     * @param $fileInfo
     * @return bool
     */
    private function isZip($fileInfo) {

        $fileType = false;

        if($fileInfo['extension'] == "zip" && $fileInfo['mime_type'] == "application/zip") {
            $fileType = "zip";
        }
        return $fileType;
    }

    /**
     * Check if the file is a word document or not
     * @param $fileInfo
     * @return bool
     */
    private function isWord($fileInfo) {

        $fileType = false;

        foreach($this->word_mime_types as $ext => $mime) {
            if($fileInfo['extension'] == $ext && $fileInfo['mime_type'] == $mime) {
                $fileType = "word";
                break;
            }
        }
        return $fileType;
    }

    /**
     * Check whether the provided file is valid or not
     * @param string $file
     * @return array
     */
    public function isValidFile($file) {
        try {
            if (!isset($file['error']) || is_array($file['error'])) {
                $response['message'] = "Invalid parameters";
                $response['error'] = 1;
                $response['data'] = null;
            } else {
                //check file error values
                switch ($file['error']) {
                    case 0:
                        $response['message'] = "The file is OK";
                        $response['error'] = 0;
                        $response['data'] = null;
                        break;
                    case 1:
                        $response['message'] = "File exceeds allowed size";
                        $response['error'] = 1;
                        $response['data'] = null;
                        break;
                    case 2:
                        $response['message'] = "File exceeds allowed size";
                        $response['error'] = 1;
                        $response['data'] = null;
                        break;
                    case 3:
                        $response['message'] = "File partially uploaded. Please try again";
                        $response['error'] = 1;
                        $response['data'] = null;
                        break;
                    case 4:
                        $response['message'] = "File not uploaded. Please try again";
                        $response['error'] = 1;
                        $response['data'] = null;
                        break;
                    case 6:
                        $response['message'] = "Missing temporary folder. Please contact server admin for help";
                        $response['error'] = 1;
                        $response['data'] = null;
                        break;
                    case 7:
                        $response['message'] = "Cannot write the file to disk. Please contact server admin for help";
                        $response['error'] = 1;
                        $response['data'] = null;
                        break;
                    case 7:
                        $response['message'] = "File upload stopped by extension. Please contact server admin for help";
                        $response['error'] = 1;
                        $response['data'] = null;
                        break;
                    default:
                        $response['message'] = "An unknown error has occured. Please contact server admin for help";
                        $response['error'] = 1;
                        $response['data'] = null;
                        break;
                }
            }
        } catch (RuntimeException $e) {
            $response['message'] = $e->getMessage();
            $response['error'] = 1;
            $response['data'] = null;
        }
        return $response;
    }

    /**
     * Upload a file
     * @param $type
     * @param $file
     * @return array
     */
    public function uploadFile($type, $file) {

        $fileInfo = $this->getFileInfo($file);

        $response['error'] = 1;
        $response['message'] = "";
        $response['data'] = null;

        $response = $this->isValidFile($file);

        if($response['error'] == 0) {
            //we check for the specific file type validity

            $fileType = false;

            switch ($type) {
                case 'image':
                    $fileType = $this->isImage($fileInfo);
                    break;
                case 'video':
                    $fileType = $this->isVideo($fileInfo);
                    break;
                case 'document':

                    if ($this->isPDF($fileInfo) !== false) {
                        $fileType = "PDF";
                    } elseif ($this->isWord($fileInfo) !== false) {
                        $fileType = "word";
                    } else if ($this->isZip($fileInfo) !== false) {
                        $fileType = "zip";
                    } else {
                        $fileType = false;
                    }
                    break;
            }

            if ($fileType == false) {
                $response['error'] = 1;
                $response['message'] = "You have selected an unaccepted file format";
            } else {

                $fileInfo['type'] = $fileType;

                // we upload the file to S3
                $destination_directory = date("Y/m/d", time());
                $destination_file_name = $fileInfo['name'] . "." . $fileInfo['extension'];

                $fileInfo['file_uri_path'] = $destination_directory;

                $isUploaded = false;

                try {

                    $isUploaded = $this
                        ->S3
                        ->init($this->s3Version, $this->s3Region, $this->s3AccessKey, $this->s3AccesSecret)
                        ->setBucket($this->s3bucketName)
                        ->uploadFile($file['tmp_name'],$destination_directory,$destination_file_name,$fileInfo['mime_type']);

                } catch (\Exception $e) {
                    print $e->getMessage();
                }

                if (!$isUploaded) {
                    $response['message'] = "Failed to upload the file to S3";
                } else {
                    $response['error'] = 0;
                    $response['message'] = 'File successfully uploaded';
                }

                $response['data'] = $fileInfo;
            }
        }
        return $response;
    }

    /**
     * @param $fileName
     * @return bool|void
     * @throws \Kuza\Krypton\Exceptions\ConfigurationException
     */
    public function deleteFileFromS3($fileName) {

        return $this
            ->S3
            ->init($this->s3Version, $this->s3Region, $this->s3AccessKey, $this->s3AccesSecret)
            ->setBucket($this->s3bucketName)
            ->deleteFile($$fileName);

    }

    /**
     * function to delete a file
     * @param string $file the file to be deleted
     * @return boolean
     */
    public function deleteFile($file) {
        if(!is_file($file)){
            return false;
        }

        unlink($file);

        if (!is_file($file)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * delete a directory and all its contents
     * @param string $dir the directory to be deleted
     * @return boolean
     */
    public function deleteDirectory($dir) {
        $return = false;
        if (is_dir($dir)) {
            $dirContents = $this->getDirContents($dir);

            foreach ($dirContents as $content) {
                $this->deleteDirectory($content);
            }
            if (rmdir($dir)) {
                $return = true;
            }
        } elseif (is_file($dir)) {
            if (unlink($dir)) {
                $return = true;
            }
        }
        return $return;
    }

    /**
     * Get the contents of a directory
     * @param string $dir the directory whose files and subdirectories are to be retrieved
     * @param string $filter filter the files to be included, if provided
     * @param array &$results
     * @return array
     */
    private function getDirContents($dir, $filter = '', &$results = array()) {
        if (is_dir($dir)) {
            $files = scandir($dir);

            foreach ($files as $key => $value) {
                $path = realpath($dir . DIRECTORY_SEPARATOR . $value);

                if (!is_dir($path)) {
                    if (empty($filter) || preg_match($filter, $path)) {
                        $results[] = $path;
                    }
                } elseif ($value != "." && $value != "..") {
                    $this->getDirContents($path, $filter, $results);
                    $results[] = $path;
                }
            }
        }

        return $results;
    }
}
