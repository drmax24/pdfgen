<?php
namespace App\Http\Controllers;

use App\Mail\PdfMail;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use mikehaertl\wkhtmlto\Pdf;

class UriToPdf extends Controller
{
    public $secureDomanList = [
        'bstd.ru',
        'toyota.ru',
        'lexus.ru',
        'audi.ru',
        'obi.ru',
        'obiclub.ru'
    ];

    public function __construct()
    {
        ini_set('max_execution_time', 11);
        ini_set('memory_limit', "256M");
    }


    public function getPdf()
    {
        $pdfFileName = '';



        if (!Input::has('target_uri')) {
            return json_response([
                'status' => 'Укажите параметр target_uri'
            ], 400);
        }

        if (!$this->isSecureDomain(Input::get('target_uri'))) {
            return json_response([
                'status' => 'Forbiddent target: ' . Input::get('target_uri')
            ], 404);
        }

        if (Input::has('pdf_file_name')) {
            $pdfFileName = Input::get('pdf_file_name');
            $ext         = strtolower(pathinfo($pdfFileName, PATHINFO_EXTENSION)) !== 'pdf';
            $pdfFileName .= '.pdf';
        }


        parse_url(Input::get('target_uri'));

        $input     = Input::all();
        $targetUri = $input['target_uri'];

        $wkhtmltopdfParams = @$input['wkhtmltopdf-params'];
        unset($input['target_url'], $input['wkhtmltopdf-params'], $wkhtmltopdfParams['binary']);
        $query = http_build_query($input);

        $params = [
            'binary' => '/usr/local/bin/wkhtmltopdf',
            //'javascript-delay' => '4000',
            //'window-status'    => 'ready-to-print'
        ];

        //http://pdfgen.microservices.local/api/uri-to-pdf?target_uri=http://toyota-tech-service.coding.dev.bstd.ru/index1.html&wkhtmltopdf-params[javascript-delay]=4000

        if ($wkhtmltopdfParams) {
            foreach ($wkhtmltopdfParams as $k => $v) {
                $params[$k] = $v;
            }
        }

        $pdf = new Pdf($params);
        //$pdf->getInternalGenerator()->setTimeout(30);
        $uri = $targetUri . '?' . $query;


        $pdf->addPage($uri);
        //$pdf->addPage('https://pages.lexus.ru/locator/');


        $pdfData = $pdf->toString();
        if ($pdfData) {
            //MaintenanceCartSession::firstOrCreate(['pdf_data' => $pdfData]);
            //saveAs(storage_path('/maintenance/pdf/Расчет ТО.pdf'))) {
        } else {
            echo $pdf->getError();
        }



        if (Input::get('email.to')) {

            $validator = \Validator::make(
                Input::all(), [
                    'email.from'    => 'required|email',
                    'email.to'      => 'required|email',
                    'email.subject' => 'required',
                    'email.body'    => 'required',
                    'pdf_file_name' => 'required',
                ]
            );
            if ($validator->fails()) {
                return json_response(['status' => 'error', 'explanation' => $validator->errors()],
                    $status = 400,
                    ['Content-Type' => 'application/json; charset=UTF-8']);
            }

            //$data = base


            Mail
                ::to(Input::get('email.to'))
                ->queue(new PdfMail(
                    Input::get('email.from'),
                    Input::get('email.subject'),
                    Input::get('email.body'),
                    Input::get('pdf_file_name'),
                    base64_encode($pdfData)));
            app('sentry')->captureMessage('Письмо добавлено в очередь: ' . Input::get('email.to'), [],
                ['level' => 'info']);
        } else {
            if ($pdfFileName && !Input::get('open_file_in_browser')) {
                // Скачать
                //header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Accept, Authorization, X-Request, X-Requested-With');
                //header('Access-Control-Allow-Origin: *');

                $pdf->send($pdfFileName);
                exit;
            } elseif ($pdfFileName) {
                //header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Accept, Authorization, X-Request, X-Requested-With');
                //header('Access-Control-Allow-Origin: *');
                //header("Content-type: application/pdf");
                //Content-Disposition: inline; filename="filename.pdf"
                header('Content-Disposition: inline; filename=' . basename($pdfFileName));

                echo $pdf->toString();
                exit;
            } else {
                // Открыть в браузере
                $pdf->send();
            }
        }

        return json_response(['status' => 'ok'],
            $status = 200
        );

        //json_response(['status' => 'ok']);
    }

    public function isSecureDomain($uri)
    {
        $parsedUrl = parse_url($uri);
        foreach ($this->secureDomanList as $secureDomain) {
            if (substr($parsedUrl['host'], -strlen($secureDomain)) === $secureDomain) {
                return true;
            }
        }
    }
}
