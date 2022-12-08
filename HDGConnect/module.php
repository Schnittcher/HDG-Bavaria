<?php

declare(strict_types=1);
    class HDGConnect extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();
            $this->RegisterPropertyBoolean('Active', true);
            $this->RegisterPropertyString('URL', '');
            $this->RegisterPropertyInteger('Intervall', 20);
            $this->RegisterTimer('HDG_Update', 0, 'HDG_Update($_IPS[\'TARGET\']);');
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

            if ($this->ReadPropertyBoolean('Active')) {
                $this->SetTimerInterval('HDG_Update', $this->ReadPropertyInteger('Intervall') * 1000);
                $this->SetStatus(102);
            } else {
                $this->SetTimerInterval('HDG_Update', 0);
                $this->SetStatus(104);
            }
        }

        public function Update()
        {
            $components = json_decode(file_get_contents(__DIR__ . '/../libs/datapoints.json'), true);

            $post = 'nodes=';

            foreach ($components as $key => $component) {
                foreach ($component as $keyComponent => $values) {
                    $last_key = @end(array_keys($values['nodes']));
                    $first_key = (array_key_first($values['nodes']));
                    foreach ($values['nodes'] as $keyNode => $node) {
                        if (($first_key == $keyNode) && ($post == 'nodes=')) {
                            $post .= $node['dataid'] . '';
                        } else {
                            if ($node['dataid'] != false) {
                                $post .= '-' . $node['dataid'];
                            }
                        }
                    }
                }
            }

            $this->SendDebug(__FUNCTION__ . ':: Post', $post, 0);

            $result = $this->sendRequest($post);
            $this->SendDebug(__FUNCTION__ . ':: Result', $result, 0);

            $Data['DataID'] = '{EE058020-CD88-B252-78DA-2920D1C7513B}';
            $Data['Data'] = $result;
            $this->SendDataToChildren(json_encode($Data));
        }

        protected function sendRequest($post)
        {
            $this->SendDebug('sendRequest :: Post Nodes', $post, 0);
            $URL = $this->ReadPropertyString('URL');
            $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $URL . '/ApiManager.php?action=dataRefresh');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $Response = curl_exec($ch);
            $HttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->SendDebug('sendRequest :: URl', $URL . '/ApiManager.php?action=dataRefresh', 0);
            $this->SendDebug('sendRequest :: Response', $Response, 0);
            $this->SendDebug('sendRequest :: HttpCode', $HttpCode, 0);
            if ($HttpCode != 200) {
                $this->LogMessage('Error: ' . $HttpCode, KL_ERROR);
                return [];
            }
            return $Response;
        }
    }