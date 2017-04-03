<?php
namespace App\Http\Controllers;

use App\Models\DealerCenters\DealerCenter;
use App\Models\Maintenance\MaintenanceCartSession;
use App\Models\Maintenance\MaintenanceModel;
use App\Models\Maintenance\MaintenancePackage;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Maintenance\MaintenanceService;
use Illuminate\Support\Facades\Mail;
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
        ini_set('max_execution_time', 30);
        ini_set('memory_limit', "256M");
    }

    public function emailPdf()
    {
        $input = \Input::all();
        /*
                {
                    "client": {
                    "first_name": "Иванов",
                    "last_name": "Иван",
                    "patronymic": "Иванович",
                    "phone": "79111234567",
                    "email": "name@domain.ru"
                },
        */
        $content = $this->generatePdf();

//        $fromName = $input['client']['first_name'];
        $fromName = 'ДЦ';
        $fromMail = $input['client']['email'];
        $subject  = 'Расчет ТО';
        $pdfFileName = 'Расчет ТО.pdf';

        $messageText = 'Ваш расчёт готов и находится во вложении.';

        Mail::raw($messageText,
            function ($message) use ($content, $fromMail, $subject, $fromName, $pdfFileName, $input) {
                $message->from($fromMail, $fromName);
                $message->to($input['client']['email'])->subject($subject);
                $message->attachData($content, $pdfFileName);
            });

//        foreach ($data as $k => $v) {
//            $data[$k]['end_production_year'] = (int)date("Y");
//        }

        return \Response::json([
            'status' => 'ok'
        ], 200, [
//            'Access-Control-Allow-Origin'      => '*',
//            'Access-Control-Allow-Credentials' => 'true',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function generatePdf()
    {
        $pdf = new GeneratePDF('', 'A4', 0, '', 10, 5, 7, 12, 9, 10, 'P');
        $pdf->loadCustomFonts();
        $pdf->SetTitle('Расчет ТО');
        $pdf->setFooter('{PAGENO}');
        $pdf->WriteHTML('<h1>Hello!</h1>');

        return $pdf->Output('', 'S');
    }

    public function getPdf()
    {
        if (!\Input::has('target_uri')) {
            return \Response::json([
                'status' => 'Укажите параметр target_url'
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        //$parsedUrl['host'];

        if (!$this->isSecureDomain(\Input::get('target_uri'))) {
            return \Response::json([
                'status' => 'Forbiddent target: ' . \Input::get('target_uri')
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }


        if (@$input['pdf_file_name']) {
            $pdfFileName = $input['file_name'];
            $ext      = strtolower(pathinfo(@$input['file_name'], PATHINFO_EXTENSION)) !== 'pdf';
            $pdfFileName .= '.pdf';
        } else {
            $pdfFileName = 'Расчет ТО для ' . @$input['file_name'] . '.pdf';
        }


        parse_url(\Input::get('target_uri'));

        $input     = \Input::all();
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
        $pdf->setTimeout(25);

        //$uri = 'http://toyota-tech-service.coding.dev.bstd.ru/index1.html?' . $query;
        $uri = $targetUri . '?' . $query;


        //'http://toyota-tech-service.coding.dev.bstd.ru/index1.html'


        $pdf->addPage($uri);
        //$pdf->addPage('https://pages.lexus.ru/locator/');

        $pdfData = $pdf->toString();
        if ($pdfData) {
            //MaintenanceCartSession::firstOrCreate(['pdf_data' => $pdfData]);
            //saveAs(storage_path('/maintenance/pdf/Расчет ТО.pdf'))) {
        } else {
            echo $pdf->getError();
        }


            $pdf->send($pdfFileName);


        return \Response::json([
            'status' => 'ok'
        ], 200, [
//            'Access-Control-Allow-Origin'      => '*',
//            'Access-Control-Allow-Credentials' => 'true',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


        // пример вызова
        // $pdfData = $this->generatePdf();


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
