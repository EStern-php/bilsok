<?php

class model
{

    public $baseUrl = "https://bilweb.se/sok?query=&type=1&limit=500";
    public $db;

    public function __construct()
    {
        $this->db = databaseModel::pdo();
    }

    public function scrapeCars($retries = 3, $baseDelay = 500)
    {
        header('Content-Type: text/html; charset=utf-8');
    

        $listUrl = $this->baseUrl;
        $listHtml = $this->curl($listUrl);
        if (empty($listHtml)) {
            echo "<pre>Kunde inte hämta listan.</pre>";
            return;
        }

        $carCards = $this->parseCarsHtml($listHtml, $listUrl);
        if (empty($carCards)) {
            echo "<pre>Hittade inga kort.</pre>";
            return;
        }

        $count = 0;
        foreach ($carCards as $card) {
            $detailUrl = $card['url'];
            $detailHtml = null;

            for ($i=0; $i<$retries; $i++) {
                $detailHtml = $this->curl($detailUrl);
                if ($detailHtml) break;
                $wait = $baseDelay * (1<<$i) + random_int(200, 600);
                $this->sleep_ms($wait);
            }
            if (!$detailHtml) continue;

            $spec = $this->parseDetails($detailHtml);
            $row = array_merge($card, $spec);

            $this->addCarsToDatabase($row);

            $count++;
            
            $this->sleep_ms(350 + random_int(150, 500));
        }
    return $count;

    }

    private function curl($url, $timeout = 15) 
    {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: Mozilla/5.0 (compatible; BilScraper/1.0)',
                'Accept: text/html,application/xhtml+xml',
            ],
        ]);
        $html = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($html !== false && $code >= 200 && $code < 300) {
            return $html;
        }
        error_log("GET fail ($code) $url" . ($err ? " :: $err" : ""));
        return null;
    }

    public function parseCarsHtml($html, $baseUrl)
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $xp = new DOMXPath($dom);

        $panel = $xp->query(
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' tabs-panel ') and " .
            "  contains(concat(' ', normalize-space(@class), ' '), ' is-active ')]"
        )->item(0);

        if ($panel) {
            $cardNodes = $xp->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' Card ')][@id]", $panel);
        } else {
            // Fallback om layouten saknar tabs
            $cardNodes = $xp->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' Card ')][@id]");
        }

        $seenIds  = [];
        $seenUrls = [];
        $items = [];

        foreach ($cardNodes as $card) {
            $id = trim($card->getAttribute('id')) ?: null;

            $a = $xp->query(".//h3[contains(@class,'Card-heading')]/a[contains(@class,'go_to_detail')]", $card)->item(0);
            if (!$a) continue;

            $href  = $a->getAttribute('href');
            $url   = $this->absolutize_url($href, $baseUrl);

            // Undvik dubletter
            if (($id && isset($seenIds[$id])) || isset($seenUrls[$url])) continue;
            if ($id) $seenIds[$id] = true;
            $seenUrls[$url] = true;

        //    $title = trim($a->textContent);

            $priceNode = $xp->query(".//p[contains(@class,'Card-mainPrice')]", $card)->item(0);
            $price     = $priceNode ? $this->to_int($priceNode->textContent) : null;

            $yearNode  = $xp->query(".//dl[contains(@class,'Card-carData')]//dt[normalize-space()='År:']/following-sibling::dd[1]", $card)->item(0);
            $year      = $yearNode ? $this->to_int($yearNode->textContent) : null;

            $milNode   = $xp->query(".//dl[contains(@class,'Card-carData')]//dt[normalize-space()='Mil:']/following-sibling::dd[1]", $card)->item(0);
            $mileage   = $milNode ? $this->to_int($milNode->textContent) : null;

            $items[] = [
                'id'      => $id,
                'url'     => $url,
                'price'   => $price,
                'year'    => $year,
                'mileage' => $mileage,
            ];
        }

        return $items;
    }

    private function parseDetails($html)
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $xp = new DOMXPath($dom);

        $ul = $xp->query("//ul[contains(@class,'List--horizontal') and contains(@class,'List--bordered')]")->item(0);
        if (!$ul) return [];

        $wanted = [
            'Regnummer'   => 'regno',
            'Märke'       => 'make',
            'Modell'      => 'model',
            'Årsmodell'   => 'model_year',
            'Mil'         => 'mileage',
            'Ort'         => 'location',
        ];

        $data = [];
        foreach ($xp->query(".//li[contains(@class,'List-item')]", $ul) as $li) {
            $labelNode = $xp->query(".//h5", $li)->item(0);
            $valueNode = $xp->query(".//p",  $li)->item(0);
            if (!$labelNode || !$valueNode) continue;

            $label = trim(preg_replace('/\s+/', ' ', $labelNode->textContent));
            $value = trim(preg_replace('/\s+/', ' ', $valueNode->textContent));
            if (isset($wanted[$label])) {
                $key = $wanted[$label];
                $data[$key] = $value;
            }
        }

        if (isset($data['mileage'])) {
            $data['mileage'] = $this->to_int($data['mileage']);
        }
        if (isset($data['model_year'])){
            $data['model_year'] = $this->to_int($data['model_year']);
        }  
        if (isset($data['regno'])){
            $data['regno'] = $this->normalize_regno($data['regno']);
        }

        return $data;
    }

    private function addCarsToDatabase($params)
    {
        $sql = 'INSERT INTO cars
      (source_id, make, model, model_year, regno, price, mileage, location)
      VALUES
      (:source_id, :make, :model, :model_year, :regno, :price, :mileage, :location)
      ON DUPLICATE KEY UPDATE
        make=VALUES(make),
        model=VALUES(model),
        model_year=VALUES(model_year),
        regno=VALUES(regno),
        price=VALUES(price),
        mileage=VALUES(mileage),
        location=VALUES(location)';

        $prepared = [
            ':source_id' => $params['id'],
            ':make' => $params['make'] ?? '',
            ':model' => $params['model'] ?? '',
            ':model_year' => $params['model_year'] ?? '',
            ':regno' => $params['regno'] ?? '',
            ':price' => $params['price'] ?? '',
            ':mileage' => $params['mileage'] ?? '',
            ':location' => $params['location'] ?? ''
        ];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($prepared);
    }

    public function getCars($getParams = [])
    {
        $make = trim($getParams['make'] ?? '');
        $year = $getParams['year'] ?? null;     
        $reg  = trim($getParams['regno'] ?? '');

        $year = ($year === '' ? null : (int)$year);
        $reg  = ($reg === '' ? null : $this->normalize_regno($reg));

        $sqlBase = "FROM cars";
        $where   = [];
        $params  = [];

        if ($make !== '') {
            $where[] = "(make = :make OR model LIKE :make_like_model)";
            $params[':make'] = $make;                 
            $params[':make_like_model'] = $make . '%';
        }

        if ($year !== null && $year > 0) {
            $where[] = "model_year = :year";
            $params[':year'] = $year;
        }

        if ($reg !== null && $reg !== '') {
            $where[] = "regno = :regno";
            $params[':regno'] = $reg;
        }

        $whereSql = $where ? (" WHERE " . implode(" AND ", $where)) : "";

        // Fast LIMIT just nu. Men man hade kunnat bygga vidare med pagenering och väljare för antal annonser.
        $sql = "SELECT id, source_id, make, model, model_year, regno, price, mileage, location
                " . $sqlBase . $whereSql . "
                ORDER BY model_year DESC, price ASC
                LIMIT 20";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'results'  => $rows,
        ];
    }

    private function sleep_ms($ms) {
         usleep($ms * 1000);
    }

    private function to_int($s) {
        if ($s === null) return null;
        if (preg_match('/(\d[\d\s]*)/', $s, $m)) {
            return (int)str_replace(' ', '', $m[1]);
        }
        return null;
    }

    private function normalize_regno($s) {
        if ($s === null) return null;
        return preg_replace('/\s+/', '', strtoupper(trim($s)));
    }

    private function absolutize_url($href, $base) {
        if (str_starts_with($href, 'http')) return $href;
        if (str_starts_with($href, '//'))  return 'https:' . $href;
        
        $p = parse_url($base);
        $root = $p['scheme'].'://'.$p['host'].(isset($p['port'])?':'.$p['port']:'');
        if (str_starts_with($href, '/')) return $root.$href;
        $dir = rtrim(dirname($p['path'] ?? '/'), '/');
        return $root.$dir.'/'.$href;
    }
}