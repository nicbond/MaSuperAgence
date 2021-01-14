<?php

namespace App\Service;

class XmlWriter extends XmlWriterAbstract
{
    public function setData($data)
    {
        if (isset($data['listPmtInf'])) {
            $listPmtInf = $data['listPmtInf'];
        } else {
            $listPmtInf[0] = $data;
        }

        $root = $this->domDocument->appendChild($this->domDocument->createElement('Document'));
        if (!isset($listPmtInf[0]['header']->hasNoPropertyInDocument)) {
            $root->setAttribute('xmlns', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03');
            $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        }

        $uniqId = uniqid();

        if (isset($data['baliseGlobal'])) {
            $baliseGlobal = $data['baliseGlobal'];
        } else {
            $baliseGlobal = 'CstmrCdtTrfInitn';
        }

        $cstmrCdtTrfInitn = $root->appendChild($this->domDocument->createElement($baliseGlobal));

        $grpHdr = $cstmrCdtTrfInitn->appendChild($this->domDocument->createElement('GrpHdr'));

        $msgId = $grpHdr->appendChild($this->domDocument->createElement('MsgId'));
        $msgId->appendChild($this->domDocument->createTextNode($listPmtInf[0]['header']->msgId));

        $creDtTm = $grpHdr->appendChild($this->domDocument->createElement('CreDtTm'));
        $creDtTm->appendChild($this->domDocument->createTextNode($listPmtInf[0]['header']->creDtTm));

        $totalAmount = 0;
        $nbTransaction = 0;
        foreach ($listPmtInf as $trans) {
            foreach ($trans['transaction'] as $transac) {
                $totalAmount += $transac->amount;
                $nbTransaction++;
            }
        }

        $nbOfTxs = $grpHdr->appendChild($this->domDocument->createElement('NbOfTxs'));
        $nbOfTxs->appendChild($this->domDocument->createTextNode($nbTransaction));

        $ctrlSum = $grpHdr->appendChild($this->domDocument->createElement('CtrlSum'));
        $ctrlSum->appendChild($this->domDocument->createTextNode($totalAmount));

        $initgPty = $grpHdr->appendChild($this->domDocument->createElement('InitgPty'));
        
        $name = $initgPty->appendChild($this->domDocument->createElement('Nm'));
        $name->appendChild($this->domDocument->createTextNode($listPmtInf[0]['header']->initgPty));

        foreach ($listPmtInf as $pmtInfElement) {
            $pmtInf = $cstmrCdtTrfInitn->appendChild($this->domDocument->createElement('PmtInf'));

            $pmtInfId = $pmtInf->appendChild($this->domDocument->createElement('PmtInfId'));
            $pmtInfId->appendChild($this->domDocument->createTextNode($pmtInfElement['header']->pmtInfId));

            $pmtMtd = $pmtInf->appendChild($this->domDocument->createElement('PmtMtd'));
            $pmtMtd->appendChild($this->domDocument->createTextNode('TRF'));

            $btchBookg = $pmtInf->appendChild($this->domDocument->createElement('BtchBookg'));
            $btchBookg->appendChild($this->domDocument->createTextNode('false'));

            $pmtTpInf = $pmtInf->appendChild($this->domDocument->createElement('PmtTpInf'));

            $svcLvl = $pmtTpInf->appendChild($this->domDocument->createElement('SvcLvl'));
            $cd = $svcLvl->appendChild($this->domDocument->createElement('Cd'));
            $cd->appendChild($this->domDocument->createTextNode('SEPA'));

            $reqdExctnDt = $pmtInf->appendChild($this->domDocument->createElement('ReqdExctnDt'));
            $reqdExctnDt->appendChild($this->domDocument->createTextNode($pmtInfElement['header']->settlementDate));

            $dbtr = $pmtInf->appendChild($this->domDocument->createElement('Dbtr'));
            $nameDebitor = $dbtr->appendChild($this->domDocument->createElement('Nm'));
            if ($baliseGlobal == 'CstmrCdtTrfInitn') {
                $nameDebitor->appendChild($this->domDocument->createTextNode($pmtInfElement['header']->debitorName));
            } else {
                $nameDebitor->appendChild($this->domDocument->createTextNode($pmtInfElement['transaction'][0]->debitorName));
            }

            $dbtAccount = $pmtInf->appendChild($this->domDocument->createElement('DbtrAcct'));
            $dbtIb = $dbtAccount->appendChild($this->domDocument->createElement('Id'));
            $dbtIban = $dbtIb->appendChild($this->domDocument->createElement('IBAN'));
            if ($baliseGlobal == 'CstmrCdtTrfInitn') {
                $dbtIban->appendChild($this->domDocument->createTextNode($this->normalize($pmtInfElement['header']->debitorIban)));
            } else {
                $dbtIban->appendChild($this->domDocument->createTextNode($this->normalize($pmtInfElement['transaction'][0]->debitorIban)));
            }

            $dbtAgent = $pmtInf->appendChild($this->domDocument->createElement('DbtrAgt'));
            $dbtFinInstnId = $dbtAgent->appendChild($this->domDocument->createElement('FinInstnId'));
            $dbtBic = $dbtFinInstnId->appendChild($this->domDocument->createElement('BIC'));
            if ($baliseGlobal == 'CstmrCdtTrfInitn') {
                $dbtBic->appendChild($this->domDocument->createTextNode($this->normalize($pmtInfElement['header']->debitorBic)));
            } else {
                $dbtBic->appendChild($this->domDocument->createTextNode($this->normalize($pmtInfElement['transaction'][0]->debitorBic)));
            }

            if (isset($pmtInfElement['transaction'][0]->ultmtdbtrprtryId)) {
                $ultmtDbtr = $pmtInf->appendChild($this->domDocument->createElement('UltmtDbtr'));

                $nameUltmultmtDbtr = $ultmtDbtr->appendChild($this->domDocument->createElement('Nm'));
                $nameUltmultmtDbtr->appendChild($this->domDocument->createTextNode($pmtInfElement['header']->initgPty));

                $idUltmtDbtr =  $ultmtDbtr->appendChild($this->domDocument->createElement('Id'));
                $orgIdUltmtDbtr = $idUltmtDbtr->appendChild($this->domDocument->createElement('OrgId'));
                $prtryIdUltmtDbtr = $orgIdUltmtDbtr->appendChild($this->domDocument->createElement('PrtryId'));
                $idUltmtDbtr1 = $prtryIdUltmtDbtr->appendChild($this->domDocument->createElement('Id'));
                $idUltmtDbtr1->appendChild($this->domDocument->createTextNode($uniqId));
            }

            if (isset($pmtInfElement['transaction'][0]->chrgBr)) {
                $chrgBr = $pmtInf->appendChild($this->domDocument->createElement('ChrgBr'));
                $chrgBr->appendChild($this->domDocument->createTextNode($pmtInfElement['transaction'][0]->chrgBr));
            }

            foreach ($pmtInfElement['transaction'] as $transaction) {
                $creditorInf = $pmtInf->appendChild($this->domDocument->createElement('CdtTrfTxInf'));

                $pmtId = $creditorInf->appendChild($this->domDocument->createElement('PmtId'));
                $endToEndId = $pmtId->appendChild($this->domDocument->createElement('EndToEndId'));
                $endToEndId->appendChild($this->domDocument->createTextNode($transaction->endToEndId));

                $creditorAmount = $creditorInf->appendChild($this->domDocument->createElement('Amt'));
                $instAmount = $creditorAmount->appendChild($this->domDocument->createElement('InstdAmt'));
                $instAmount->setAttribute('Ccy', 'EUR');
                $instAmount->appendChild($this->domDocument->createTextNode($transaction->amount));

                $cdtrAgt = $creditorInf->appendChild($this->domDocument->createElement('CdtrAgt'));
                $cdtFinInstnId = $cdtrAgt->appendChild($this->domDocument->createElement('FinInstnId'));
                $cdtBic = $cdtFinInstnId->appendChild($this->domDocument->createElement('BIC'));
                $cdtBic->appendChild($this->domDocument->createTextNode($this->normalize($transaction->creditorBic)));

                $cdtr = $creditorInf->appendChild($this->domDocument->createElement('Cdtr'));

                $nameCdtr = $cdtr->appendChild($this->domDocument->createElement('Nm'));
                $nameCdtr->appendChild($this->domDocument->createTextNode($transaction->creditorName));

                if (isset($transaction->cdtrprtryId)) {
                    $cdtrId = $cdtr->appendChild($this->domDocument->createElement('Id'));
                    $cdtrorgId = $cdtrId->appendChild($this->domDocument->createElement('OrgId'));
                    $cdtrPrtryId = $cdtrorgId->appendChild($this->domDocument->createElement('PrtryId'));
                    $cdtrPrtryIdId = $cdtrPrtryId->appendChild($this->domDocument->createElement('Id'));
                    $cdtrPrtryIdId->appendChild($this->domDocument->createTextNode($transaction->cdtrprtryId));
                }

                $cdtrAcct = $creditorInf->appendChild($this->domDocument->createElement('CdtrAcct'));
                $cdtrId = $cdtrAcct->appendChild($this->domDocument->createElement('Id'));
                $cdtIban = $cdtrId->appendChild($this->domDocument->createElement('IBAN'));
                $cdtIban->appendChild($this->domDocument->createTextNode($this->normalize($transaction->creditorIban)));
                
                if (isset($transaction->ustrd)) {
                    $rmtInf = $creditorInf->appendChild($this->domDocument->createElement('RmtInf'));
                    $ustrd = $rmtInf->appendChild($this->domDocument->createElement('Ustrd'));
                    $ustrd->appendChild($this->domDocument->createTextNode($transaction->ustrd));
                }
            }
        }
    }

    protected function normalize($not_normalized)
    {
        $normalized = strtoupper(str_replace(' ', '', $not_normalized));
        return $normalized;
    }
}
