<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/HDGModule.php';
    class Puffer extends HDGModule
    {
		public static $Variables = [];

        public function Create()
        {
            //Never delete this line!
            parent::Create();
        }

        public function Destroy()
        {
            //Never delete this line!
            parent::Destroy();
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
            $datapoints = json_decode(file_get_contents(__DIR__ . '/../libs/datapoints.json'), true);

            foreach ($datapoints['puffer'] as $key => $puffer) {
                if ($puffer['typeName'] == '3Temp') { //So lange es keine andere Auswahl gibt fest hinterlegt
                    foreach ($puffer['nodes'] as $keyNode => $node) {
                        $Variable = [$node['ident'], $node['name'], $node['type'], $node['profile'], $node['dataid'], $node['action'], $node['keep']];
                        array_push(self::$Variables, $Variable);
                    }
                }
            }

            parent::ApplyChanges();
        }

        public function ReceiveData($JSONString)
        {
			$this->SendDebug('JSON',$JSONString,0);
            $JSONData = json_decode($JSONString, true);
            $data = json_decode($JSONData['Data'], true);
            foreach ($data as $key => $value) {
                $this->SetValue($value['id'], $value['text']);
            }
        }
    }