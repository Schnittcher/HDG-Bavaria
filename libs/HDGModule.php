<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/SymconModulHelper/VariableProfileHelper.php';

class HDGModule extends IPSModule
{
    use VariableProfileHelper;

    public function Create()
    {
        parent::Create();
        $this->ConnectParent('{C164EB47-32B6-C0DA-71E0-E5569EA8D842}');

        $Variables = [];
        foreach (static::$Variables as $Pos => $Variable) {
            $Variables[] = [
                'Ident'        => str_replace(' ', '', $Variable[0]),
                'Name'         => $this->Translate($Variable[1]),
                'VarType'      => $Variable[2],
                'Profile'      => $Variable[3],
                'Node'         => $Variable[4],
                'Action'       => $Variable[5],
                'Pos'          => $Pos + 1,
                'Keep'         => $Variable[6]
            ];
        }
        $this->RegisterPropertyString('Variables', json_encode($Variables));
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $NewRows = static::$Variables;
        $NewPos = 0;
        $Variables = json_decode($this->ReadPropertyString('Variables'), true);
        foreach ($Variables as $Variable) {
            $VariableActive = $Variable['Keep'];

            @$this->MaintainVariable($Variable['Ident'], $Variable['Name'], constant($Variable['VarType']), $Variable['Profile'], $Variable['Pos'], $VariableActive);
            if ($Variable['Action'] && $VariableActive) {
                $this->EnableAction($Variable['Ident']);
            }
            foreach ($NewRows as $Index => $Row) {
                if ($Variable['Ident'] == str_replace(' ', '', $Row[0])) {
                    unset($NewRows[$Index]);
                }
            }
            if ($NewPos < $Variable['Pos']) {
                $NewPos = $Variable['Pos'];
            }
        }

        if (count($NewRows) != 0) {
            foreach ($NewRows as $NewVariable) {
                $Variables[] = [
                    'Ident'        => str_replace(' ', '', $NewVariable[0]),
                    'Name'         => $this->Translate($NewVariable[1]),
                    'VarType'      => $NewVariable[2],
                    'Profile'      => $NewVariable[3],
                    'Node'         => $NewVariable[4],
                    'Action'       => $NewVariable[5],
                    'Pos'          => ++$NewPos,
                    'Keep'         => $NewVariable[6]
                ];
            }
            IPS_SetProperty($this->InstanceID, 'Variables', json_encode($Variables));
            IPS_ApplyChanges($this->InstanceID);
            return;
        }
    }

    public function resetVariables()
    {
        $NewRows = static::$Variables;
        $Variables = [];
        foreach ($NewRows as $Pos => $Variable) {
            $Variables[] = [
                'Ident'        => str_replace(' ', '', $Variable[0]),
                'Name'         => $this->Translate($Variable[1]),
                'VarType'      => $Variable[2],
                'Profile'      => $Variable[3],
                'Node'         => $Variable[4],
                'Action'       => $Variable[5],
                'Pos'          => $Pos + 1,
                'Keep'         => $Variable[6]
            ];
        }
        IPS_SetProperty($this->InstanceID, 'Variables', json_encode($Variables));
        IPS_ApplyChanges($this->InstanceID);
        return;
    }

    protected function SetValue($Ident, $Value)
    {
        if (@$this->GetIDForIdent($Ident)) {
            $this->SendDebug('SetValue :: ' . $Ident, $Value, 0);
            parent::SetValue($Ident, $Value);
        }
    }

    protected function refreshData()
    {
        $Variables = json_decode($this->ReadPropertyString('Variables'), true);
        $post = 'nodes=';

        $last_key = @end(array_keys($Variables));

        foreach ($Variables as $key => $Variable) {
            if ($key == $last_key) {
                $post .= $Variable['Node'] . '';
            } else {
                $post .= $Variable['Node'] . '-';
            }
        }
        return $this->sendRequest($post);
    }

    protected function sendRequest($post)
    {
        $this->SendDebug('sendRequest :: Post Nodes', $post, 0);
        $URL = $this->ReadPropertyString('URL');
        $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL. '/ApiManager.php?action=dataRefresh');
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
        return json_decode($Response,true);
    }
}