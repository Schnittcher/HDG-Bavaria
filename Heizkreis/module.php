<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/HDGModule.php';
	class Heizkreis extends HDGModule
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

            foreach ($datapoints['heizkreis'] as $key => $heizkreis) {
                if ($heizkreis['typeName'] == 'Standard') { //So lange es keine andere Auswahl gibt fest hinterlegt
                    foreach ($heizkreis['nodes'] as $keyNode => $node) {
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