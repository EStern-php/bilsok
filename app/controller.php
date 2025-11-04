<?php

class controller
{

    public $model;

    public $scrapeCount = null;

    public function __construct() 
    {
        $this->model = new model();
    }

    public function execute()
    {

        $action = $_GET['action'] ?? '';
        switch ($action) {

            case 'ajax-search':
                //sök
                $carData = $this->ajaxSearch();
                die();
                break;

            case 'scrape-cars':
                //Scrape
                $this->scrapeCount = $this->model->scrapeCars();
                break;

            default:
                //Visa sida
                break;
        }

        $this->renderView();
    }

    public function ajaxSearch(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Method Not Allowed']);
            return;
        }

        $make = $_GET['make'] ?? '';
        $year = $_GET['year'] ?? '';
        $regno = $_GET['regno'] ?? '';

        try {
            $data = $this->model->getCars([
                'make' => $make,
                'year' => $year,
                'regno' => $regno
            ]);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Server error', 'msg' => $e->getMessage()]);
        }
    }

    public function renderView()
    {
        include_once('view.php'); 
    }
}