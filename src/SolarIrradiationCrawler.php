<?php

namespace TheDevick\SolarIrradiationCrawler;

use GuzzleHttp\Client;

class SolarIrradiationCrawler
{
    public function __construct(
        private Client $client,
        private int $scale,
    ) {
    }

    /**
     * @return numeric-string
     */
    private function translateNumber(string $brazilianNumber): string
    {
        $value = str_replace(',', '.', $brazilianNumber);

        if (!is_numeric($value)) {
            throw new \Exception('Non-Numeric value');
        }

        return $value;
    }

    /**
     * @return array{
     *  January: numeric-string
     *  February: numeric-string
     *  March: numeric-string
     *  April: numeric-string
     *  May: numeric-string
     *  June: numeric-string
     *  July: numeric-string
     *  August: numeric-string
     *  September: numeric-string
     *  October: numeric-string
     *  November: numeric-string
     *  December: numeric-string
     * }
     */
    public function crawl(string $latitude, string $longitude): array
    {
        $response = $this->client->post(
            'https://www.cresesb.cepel.br/index.php',
            [
                'form_params' => [
                    'latitude_dec' => $latitude,
                    'latitude' => '-' . $latitude,
                    'hemi_lat' => '0',
                    'longitude_dec' => $longitude,
                    'longitude' => '-' . $longitude,
                    'formato' => '1',
                    'lang' => 'pt',
                    'section' => 'sundata',
                ]
            ]
        );

        //add this line to suppress any warnings
        libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        $doc->loadHTML($response->getBody()->getContents());

        $xpath = new \DOMXPath($doc);

        /**
         * @var \DOMElement[] $table
         */
        $table = $xpath->query('//table[contains(concat(" ",normalize-space(@id)," ")," tb_sundata ")][1]/tbody/tr[1]/td');

        $values = [
            'January' => bcmul($this->translateNumber($table[8]->textContent), '31', $this->scale),
            'February' => bcmul($this->translateNumber($table[9]->textContent), '28', $this->scale),
            'March' => bcmul($this->translateNumber($table[10]->textContent), '31', $this->scale),
            'April' => bcmul($this->translateNumber($table[11]->textContent), '30', $this->scale),
            'May' => bcmul($this->translateNumber($table[12]->textContent), '31', $this->scale),
            'June' => bcmul($this->translateNumber($table[13]->textContent), '30', $this->scale),
            'July' => bcmul($this->translateNumber($table[14]->textContent), '31', $this->scale),
            'August' => bcmul($this->translateNumber($table[15]->textContent), '31', $this->scale),
            'September' => bcmul($this->translateNumber($table[16]->textContent), '30', $this->scale),
            'October' => bcmul($this->translateNumber($table[17]->textContent), '31', $this->scale),
            'November' => bcmul($this->translateNumber($table[18]->textContent), '30', $this->scale),
            'December' => bcmul($this->translateNumber($table[19]->textContent), '31', $this->scale),
        ];

        return $values;
    }
}