<?php
namespace trejeraos;

use \DOMDocument;
use \Exception;
use \ZipArchive;
use trejeraos\DOMSelector;

class K3bProject {
    protected $default_xml_file;
    protected $project_name;

    /**
     * @var DOMDocument
     */
    protected $document;

    /**
     * @var trejeraos\DOMSelector
     */
    protected $dom;


    public function __construct($default_xml_file) {
        $this->default_xml_file = $default_xml_file;

        $this->loadXMLFile();
    }


    public function createProject($folder) {
        $folder = realpath($folder);

        if ($folder === false) {
            throw new Exception('Folder does not exist.');
        }

        $this->project_name = basename($folder);

        $this->dom->querySelector('header volume_id')->nodeValue = $this->project_name;

        $projectElement = $this->dom->querySelector('k3b_data_project');
        $filesElement = $this->document->createElement('files');
        $projectElement->appendChild($filesElement);

        $this->addDirectory($folder, $filesElement);
    }


    public function getDOMDocument() {
        return $this->document;
    }

    public function saveProject($output_dir = null) {
        if (is_null($output_dir)) {
            $output_dir = getcwd();
        }

        $temp_dir = tempnam(sys_get_temp_dir(), 'K3bProject');
        unlink($temp_dir);
        mkdir($temp_dir);

        $data_file = $temp_dir . "/maindata.xml";
        $mime_file = $temp_dir . "/mimetype";

        $this->document->save($data_file);
        file_put_contents($mime_file, "application/x-k3b");

        $project_file = $output_dir . '/' . $this->project_name . ".k3b";

        if (file_exists($project_file)) {
            throw new Exception("File already exists <$project_file>\n");
        }

        $zip = new ZipArchive();

        if ($zip->open($project_file, ZipArchive::CREATE)!==TRUE) {
            throw new Exception("cannot open <$project_file>\n");
        }

        $zip->addFile($mime_file, basename($mime_file));
        $zip->addFile($data_file, basename($data_file));
        $zip->close();

        //TODO: clean up temporary files
    }




    protected function loadXMLFile()
    {
        $this->document = new DOMDocument();

        $this->document->preserveWhiteSpace = false;
        $this->document->formatOutput = true;

        $this->document->load($this->default_xml_file);

        $this->dom = new DOMSelector($this->document);
    }


    protected function addDirectory($folder, $element) {
        $dom = $this->document;

        $dirList = array_diff(scandir($folder), array('..', '.'));

        foreach($dirList as $file) {
            $fullPath = realpath($folder.'/'.$file);

            if ($fullPath === false) {
                throw new Exception('File does not exist.');
            }

            if (is_dir($fullPath)) {
                $dirElement = $dom->createElement('directory');
                $dirAttrName = $dom->createAttribute('name');
                $dirAttrName->value = $file;
                $dirElement->appendChild($dirAttrName);
                $element->appendChild($dirElement);
                $this->addDirectory($fullPath, $dirElement);
            } else {
                $fileElement = $dom->createElement('file');
                $fileAttrName = $dom->createAttribute('name');
                $fileAttrName->value = $file;
                $fileElement->appendChild($fileAttrName);
                $element->appendChild($fileElement);

                $urlElement = $dom->createElement('url', $fullPath);
                $fileElement->appendChild($urlElement);
            }
        }
    }
}
