<?php

define('__ROOT__', dirname(dirname(__FILE__)));
define('__MODULE__', dirname(__FILE__));

require_once(__ROOT__ . '/libs/helpers/autoload.php');
require_once(__MODULE__ . '/EVCCRegister.php');

/**
 * Class VictronModbus
 * IP-Symcon Victron Modbus Module
 *
 * @version     0.1
 * @category    Symcon
 * @package     EVCC
 * @author      Hermann Dötsch <info@doetsch-hermann.de>
 * @link        https://github.com/symcode/EVCC
 *
 */

	class EVCC extends Module
	{
        use InstanceHelper;
        public $data = [];
        private $update = true;
        private $applied = false;
        protected $profile_mappings = [];
        protected $archive_mappings = [];

        public function Create()
		{
			//Never delete this line!
			parent::Create();

            // register public properties
            $this->RegisterPropertyString('ip', '');
            $this->RegisterPropertyInteger('port', 7070);
            $this->RegisterPropertyInteger('interval', 10);
            // $this->RegisterPropertyBoolean("log", false);

            // register timers
            $this->RegisterTimer('UpdateData', 0, $this->_getPrefix() . '_UpdateValues($_IPS[\'TARGET\'], false);');
		}

        /**
         * execute, when kernel is ready
         */
        protected function onKernelReady()
        {
            $this->applied = true;

            // update timer
            $this->SetTimerInterval('UpdateData', $this->ReadPropertyInteger('interval') * 1000);

            // $this->SaveData();

        }
        /**
         * Read config
         */
        private function ReadConfig()
        {
            // read config
            $this->ip = $this->ReadPropertyString('ip');
            $this->port = $this->ReadPropertyInteger('port');

            // check config
            if (!$this->ip || !$this->port) {
                exit(-1);
            }

            // create EVCC instance
            if ($this->ip && $this->port) {


                // check register on apply changes in configuration
                if ($this->applied) {
                    try {
                        // Curl Prozedur
                    } catch (Exception $e) {
                        $this->SetStatus(202);
                        exit(-1);
                    }
                }
            }

            // status ok
            $this->SetStatus(102);
        }

        /**
         * Update everything
         */
        public function Update()
        {
            $this->UpdateValues();
        }
        /**
         * read & update update registers
         * @param bool $applied
         */
        public function UpdateValues($applied = false)
        {

            $this->update = 'values';
            $this->ReadData(EVCCRegister::value_addresses);

        }

        /**
         * read & update device registers EVCC_UpdateDevice
         * @param bool $applied
         */
        /**
         * save data to variables
         */
        public function SaveData()
        {
            // loop data and create variables
            $position = ($this->update == 'values') ? count(EVCCRegister::value_addresses) - 1 : 0;
            foreach ($this->data AS $key => $value) {
                $this->CreateVariableByIdentifier([
                    'parent_id' => $this->InstanceID,
                    'name' => $key,
                    'value' => $value,
                    'position' => $position
                ]);
                $position++;
            }
        }

        private function ReadData(array $addresses)
        {
            // read config
            $this->ReadConfig();

            // read data
            foreach ($addresses AS $address => $config) {
                try {
                    // wait some time before continue
                    if (count($addresses) > 2) {
                        IPS_Sleep(200);
                    }

                    // read register
                    $value = $this->Api('state');
                    print_r($value);

                    // continue if value is still an array
                    if (is_array($value)) {
                        continue;
                    }

                    // map value
                    if (isset($config['mapping'][$value])) {
                        $value = $this->Translate($config['mapping'][$value]);
                    }

                    // set profile
                    if (isset($config['profile']) && !isset($this->profile_mappings[$config['name']])) {
                        $this->profile_mappings[$config['name']] = $config['profile'];
                    }

                    // set archive
                    if (isset($config['archive'])) {
                        $this->archive_mappings[$config['name']] = $config['archive'];
                    }

                    // append data
                    $this->data[$config['name']] = $value;
                } catch (Exception $e) {
                }
            }

            // save data
            $this->SaveData();
        }


        /**
         * Api to EVCC
         * @param string $request
         * @return array
         */
        public function Api($request)
        {
            // read config
            $this->ip = $this->ReadPropertyString('ip');
            $this->port = $this->ReadPropertyInteger('port');

            // build url
            $url = 'http://'.$this->ip.':'.$this->port.'/api'.'/'.$request;
            $this->_log($url);

            // default data
            $data = [];

            // curl options
            $curlOptions = [
                CURLOPT_TIMEOUT => 10,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    // 'Authorization: Bearer ' . $this->token,
                    'Content-Type: application/json',
                    'Connection: keep-alive',
                    'Accept-Encoding: gzip',
                    'User-Agent: okhttp/3.2.0'
                ]
            ];

            // call api
            $ch = curl_init($url);
            curl_setopt_array($ch, $curlOptions);
            $links = curl_exec($ch);

            // get links
            if ($links = json_decode($links, true)) {

                #var_dump($links);
                #exit;

                /**
                 * $links = [
                    'currentReadings' => $links['_links']['currentReadings']['href'],
                    'consumption' => $links['_links']['consumption']['href'],
                    'profile' => $links['_links']['profile']['href'],
                    'consumptionCurrentMonth' => $links['_links']['consumptionCurrentMonth']['href']
                ];
                 */

                if (isset($links[$request])) {
                    curl_setopt($ch, CURLOPT_URL, $links[$request]);

                    $result = curl_exec($ch);
                    $data = json_decode($result, true);
                    $this->_log($data);
                }
            }

            // close curl
            curl_close($ch);

            // return data
            return $data;
        }

    }