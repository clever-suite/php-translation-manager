<?php

namespace CleverSuite;

class TranslationManager {
    private $api_key;
    private $jwt;
    private $host = 'https://api.clever-translate.com/graphql';

    public function __construct($api_key) {
        $this->api_key = $api_key;
        $this->jwt = '';
    }

    public function isAuthenticated() {
        return isset($this->jwt) && strlen($this->jwt) > 0;
    }

    public function setHost($host) {
        $this->host = $host;
    }

    public function authenticate() {
        $query = "
            mutation {
                authenticateApi(input: {pApiKey: \"$this->api_key\" }) {
                    jwt
                }
            }
        ";

        $data = $this->executeQuery($query);
        try {
            $this->jwt = $data['data']['authenticateApi']['jwt'];
        } catch (Exception $error) {
            echo $error;
        }
    }

    public  function languages() {
        if (!$this->jwt) {
            $this->authenticate();
        }

        $query = "
            query {
                languages(condition: {active: 1}) {
                    nodes {
                        active
                        name
                        code
                    }
                }
            }
        ";

        try {
            $data = $this->executeQuery($query);
            return $data['data']['languages']['nodes'];
        } catch (Exception $error) {
            echo $error;
        }

        return [];
    }

    public  function namespaces() {
        if (!$this->jwt) {
            $this->authenticate();
        }

        $query = "
            query {
                namespaces {
                    nodes {
                        name
                        id
                    }
                }
            }
        ";

        try {
            $data = $this->executeQuery($query);
            return $data['data']['namespaces']['nodes'];
        } catch (Exception $error) {
            echo $error;
        }

        return [];
    }

    public  function translations($namespace, $language) {
        if (!$this->jwt) {
            $this->authenticate();
        }

        $query = "
            query {
                getTranslations(pNamespace: \"$namespace\", pLanguage: \"$language\") {
                    nodes {
                        key
                        value
                    }
                }
            }
        ";

        try {
            $data = $this->executeQuery($query);
            return $data['data']['getTranslations']['nodes'];
        } catch (Exception $error) {
            echo $error;
        }

        return [];
    }

    public  function import_single($language, $namespace, $key, $value) {
        if (!$this->jwt) {
            $this->authenticate();
        }

        $query = "
            mutation {
                importSingle(
                    input: {pKey: \"$key\", pLanguage: \"$language\", pNamespace: \"$namespace\", pValue: \"$value\"}
                ) {
                    integer
                }
            }
        ";

        try {
            $data = $this->executeQuery($query);
            return $data['data']['importSingle']['integer'];
        } catch (Exception $error) {
            echo $error;
        }

        return 0;
    }
    
    public  function import($language, $namespace, $texts) {
        if (!$this->jwt) {
            $this->authenticate();
        }

        $variables = [
            'language' => $language,
            'namespace' => $namespace,
            'texts' => $texts
        ];

        $query = "
            mutation import(\$language: String!, \$namespace: String!, \$texts: JSON!) {
                import(
                    input: {pLanguage: \$language, pNamespace: \$namespace,  pTranslationArray: \$texts}
                ) {
                    integer
                }
            }
        ";

        try {
            $data = $this->executeQuery($query, $variables);
            return $data['data']['import']['integer'];
        } catch (Exception $error) {
            echo $error;
        }

        return 0;
    }

    public  function add($namespace, $keys) {
        if (!$this->jwt) {
            $this->authenticate();
        }

        $variables = [
            'namespace' => $namespace,
            'keys' => $keys
        ];

        $query = "
            mutation add(\$namespace: String, \$keys: [String]) {
                add(
                    input: { pNamespace: \$namespace,  pKeyArray: \$keys}
                ) {
                    integer
                }
            }
        ";

        try {
            $data = $this->executeQuery($query, $variables);
            return $data['data']['add']['integer'];
        } catch (Exception $error) {
            echo $error;
        }

        return 0;
    }

    private function executeQuery($query, $variables = []) {
        try {
            $headers = [
                'Content-Type: application/json',
            ];
    
            if ($this->jwt) {
                $headers[] = 'Authorization: Bearer ' . $this->jwt;
            }
    
            $ch = curl_init($this->host);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query, 'variables' => $variables]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
            curl_close($ch);
    
            if ($httpCode !== 200) {
                throw new Exception('HTTP error: ' . $httpCode);
            }
    
            $data = json_decode($response, true);
    
            if (isset($data['errors'])) {
                throw new Exception(json_encode($data['errors']));
            }
    
            return $data;
        } catch (Exception $e) {
            echo 'Error executing GraphQL query to ' . $this->host . ': ' . $e->getMessage();
            throw $e;
        }
    }

    public function export($path, $namespaces = null) {
        $languages = $this->languages();

        if (!$namespaces) {
            $namespaceArray = $this->namespaces();
            $namespaces = array_map(function($namespace) {
                return $namespace['name'];
            }, $namespaceArray);
        }

        if (!$languages) {
            return;
        }

        foreach ($languages as $language) {
            if (!file_exists($path . '/' . $language['code'])) {
                mkdir($path . '/' . $language['code'], 0777, true);
            }

            if (!isset($namespaces)) {
                return;
            }

            foreach ($namespaces as $namespace) {
                echo 'Exporting ' . $namespace . ' for ' . $language['code'] . PHP_EOL;

                $translations = $this->translations($namespace, $language['code']);

                $resultObject = [];

                foreach ($translations as $item) {
                    $resultObject[$item['key']] = $item['value'];
                }

                $jsonContent = json_encode($resultObject, JSON_PRETTY_PRINT);

                file_put_contents($path . '/' . $language['code'] . '/' . $namespace . '.json', $jsonContent);
            }
        }
    }
}
?>