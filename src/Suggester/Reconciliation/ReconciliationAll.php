<?php
namespace Wikidata\Suggester\Reconciliation;

use Wikidata\Suggester\SuggesterInterface;
use Zend\Http\Client;

class ReconciliationAll implements SuggesterInterface
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieve suggestions from the Wikidata OpenRefine Reconciliation API.
     *
     * @see https://wikidata.reconci.link/
     * @see https://github.com/OpenRefine/OpenRefine/wiki/Reconciliation-Service-API
     * @param string $query
     * @param string $lang
     * @return array
     */
    public function getSuggestions($query, $lang = "en")
    {
        $params = [
            'prefix' => $query, 
            'limit' => 50,
        ];
        if ($lang) {
            // Same as in Geonames code, using an ISO-639 2-letter language code. // Remove the first underscore and anything after it ("zh_CN" becomes "zh").
            $language = strstr($lang, '_', true) ?: $lang;
        } else {
            // Fall back to English
            $language = 'en';
        };
        $uri = sprintf('https://wikidata.reconci.link/%s/suggest/entity', $language);
        $response = $this->client
        ->setUri($uri)
        ->setParameterGet($params)
        ->send();
        if (!$response->isSuccess()) {
            return [];
        }

        // Parse the JSON response.
        $suggestions = [];
        $results = json_decode($response->getBody(), true);
        // return $results;
        // die();
        foreach ($results['result'] as $result) {
            $info = [];
            if (isset($result['id']) && $result['id']) {
                $info[] = sprintf('ID: %s', $result['id']);
            }
            if (isset($result['name']) && $result['name']) {
                $info[] = sprintf('Title: %s', $result['name']);
            }
            if (isset($result['description']) && $result['description']) {
                $info[] = sprintf('Description: %s', $result['description']);
            }

            $suggestions[] = [
                'value' => $result['name'],
                'data' => [
                    'uri' => sprintf('https://www.wikidata.org/entity/%s', $result['id']),
                    'info' => implode("\n", $info),
                ],
            ];
        }

        return $suggestions;
    }
}
