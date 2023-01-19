<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/HDGModule.php';
    class Zufuehrung extends HDGModule
    {
        public static $Variables = [];

        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterProfileInteger('HDG.KG', 'Tree', '', ' kg', 0, 0, 1);
            $this->RegisterProfileFloat('HDG.Tonne', 'Tree', '', ' t', 0, 0, 0.01, 2);
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

            foreach ($datapoints['zufuehrung'] as $key => $zufuehrung) {
                if ($zufuehrung['typeName'] == 'Standard') { //So lange es keine andere Auswahl gibt fest hinterlegt
                    foreach ($zufuehrung['nodes'] as $keyNode => $node) {
                        $Variable = [$node['ident'], $node['name'], $node['type'], $node['profile'], $node['dataid'], $node['action'], $node['keep']];
                        array_push(self::$Variables, $Variable);
                    }
                }
            }

            parent::ApplyChanges();
        }

        public function ReceiveData($JSONString)
        {
            $this->SendDebug('JSON', $JSONString, 0);
            $JSONData = json_decode($JSONString, true);
            $data = json_decode($JSONData['Data'], true);

            foreach ($data as $key => $value) {
                $id = $value['id'];
                switch ($id) {
                    case 21006:
                    case 21007:
                        $value = explode(' ', $value['text']);
                        $this->SetValue($id, $value[0]);
                        break;
                    case 21008: //Letzte Füllung
                        $value = explode(' ', $value['text']);
                        //Datum letzter Füllung setzen
                        $this->SetValue('21008Datum', $value[0]);
                        $menge = substr($value[1], 0, -2); //kg entfernen
                        $this->SetValue($id, $menge);
                        break;
                    case 21005:
                        $this->SendDebug('Gesamvebrauch', $value['text'], 0);
                        $menge = explode(' ', $value['text']);
                        $menge = $menge[0] / 100;
                        $this->SetValue($id, $menge);
                        break;
                    case 1411:
                    case 1412:
                    case 1413:
                    case 1414:
                    case 1415:
                    case 1416:
                    case 1417:
                    case 1418:
                        if ($value['text'] == 'Aus') {
                            $this->SetValue($value['id'], false);
                        } else {
                            $this->SetValue($value['id'], true);
                        }
                        break;
                    default:
                    $this->SetValue($id, $value['text']);
                        break;
                }
            }
        }
    }