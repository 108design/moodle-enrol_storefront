<?php
/**
 * File documentation for lang/de/enrol_storefront.php.
 *
 * @package   enrol_storefront
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>

$string['pluginname'] = 'Storefront-Kurskauf';
$string['licensepolicyproduct'] = 'Kurskauf';
$string['pluginname_desc'] = 'Schreibt Nutzer/innen nach einem verifizierten Kurskauf im 108design Storefront ein.';
$string['storefront:config'] = 'Einschreibemethode Storefront-Kurskauf konfigurieren';
$string['storefront:manage'] = 'Einschreibungen aus Storefront-Kurskäufen verwalten';
$string['storefront:unenrol'] = 'Storefront-Kund/innen ausschreiben';
$string['product'] = 'Storefront-Produkt';
$string['product_help'] = 'Verknüpft diesen Kurs mit einem Storefront-Lizenz-, Software- oder Kursprodukt. Ein Produkt kann mehrere Kurse enthalten; derselbe Kurs kann zu mehreren Produkten gehören.';
$string['productmissing'] = 'Bitte ein verfügbares Storefront-Kursprodukt auswählen.';
$string['productmapped'] = 'Dieses Produkt ist bereits mit diesem Kurs verknüpft.';
$string['instanceexists'] = 'Dieser Kurs besitzt bereits die Einschreibemethode Storefront-Kurskauf.';
$string['restorehint'] = 'Wiederhergestellter, nicht aktiver Storefront-Hinweis: {$a}';
$string['purchasenote'] = 'Kund/innen kaufen diesen Kurs im Store. Eine verifizierte bezahlte Bestellung gewährt Zugriff, sobald sie mit dem Moodle-Konto verknüpft wurde.';
$string['gotostore'] = 'Im Store ansehen';
$string['courselink'] = '{$a->product} · {$a->course}';
$string['mappingunavailable'] = 'Dieses Storefront-Produkt ist nicht mit einer verfügbaren Kurs-Einschreibemethode verknüpft.';
$string['subscriptionexpiryinvalid'] = 'Der verifizierte Abrechnungszeitraum gewährt keinen aktuellen Kurszugang.';
$string['privacy:metadata:enrol_storefront_purchase'] = 'Speichert die Beziehung zwischen einer verifizierten Storefront-Bestellung und dem Kurszugang.';
$string['privacy:metadata:enrol_storefront_purchase:userid'] = 'Kund/in, der oder die den Kurszugang erhalten hat.';
$string['privacy:metadata:enrol_storefront_purchase:orderid'] = 'Storefront-Bestellung, die den Zugang gewährt hat.';
$string['privacy:metadata:enrol_storefront_purchase:orderlineid'] = 'Unveränderliche Storefront-Bestellposition, die erfüllt wurde.';
$string['privacy:metadata:enrol_storefront_purchase:courseid'] = 'Freigeschalteter Kurs.';
$string['privacy:metadata:enrol_storefront_purchase:status'] = 'Aktueller Auslieferungsstatus.';
$string['privacy:metadata:enrol_storefront_purchase:timestart'] = 'Beginn des Zugangs.';
$string['privacy:metadata:enrol_storefront_purchase:timeend'] = 'Ende des Zugangs oder null für unbegrenzten Zugang.';
$string['privacy:metadata:enrol_storefront_purchase:timecreated'] = 'Zeitpunkt der Erstellung.';
$string['privacy:metadata:enrol_storefront_purchase:timemodified'] = 'Zeitpunkt der letzten Änderung.';
$string['licensepolicydegradation'] = 'Die Kursauslieferung ist in Storefront enthalten und besitzt kein eigenes Lizenz-Gate. Die normale Abhängigkeitsprüfung bleibt bestehen.';

// Shared independent signed update protection.
$string['updateguard_heading'] = 'Update angehalten';
$string['updateguard_component'] = 'Das Paket für {$a} wurde abgewiesen.';
$string['updateguard_hint'] = 'Verwende ein freigegebenes Paket für die installierte Ausgabe. Du kannst den Vorgang erneut prüfen.';
$string['updateguard_unchanged'] = 'Dieser Vorgang hat keine Plugin-Dateien ersetzt.';
$string['updateguard_help'] = 'Das installierte Plugin prüft vor dem Ersetzen die Herausgebersignatur, den Paketinhalt, den erlaubten Updatekanal und die Kompatibilität. Eine gültige Signatur erlaubt keinen Wechsel auf eine andere Ausgabe. Wird ein Paket abgewiesen, stoppt die gesamte Sammelaktion.';
$string['updateguard_signature'] = 'Die Herausgebersignatur oder der Paketinhalt konnte nicht bestätigt werden.';
$string['updateguard_policy'] = 'Das Paket passt nicht zur installierten Ausgabe oder zum erlaubten Paketprofil.';
$string['updateguard_channel'] = 'Updates über diesen Kanal sind für dieses Plugin nicht freigegeben.';
$string['updateguard_metadata'] = 'Die Versions- oder Abhängigkeitsangaben sind nicht konsistent.';
$string['updateguard_dependency'] = 'Eine benötigte Plugin-Version fehlt. Aktualisiere die Abhängigkeit gemeinsam mit diesem Plugin oder vorher.';
$string['updateguard_approval'] = 'Die Pakete oder der installierte Stand haben sich seit der Vorschau geändert. Prüfe den Vorgang erneut.';
$string['updateguard_busy'] = 'Eine andere Installation läuft. Versuche es nach deren Abschluss erneut.';
$string['updateguard_installed'] = 'Der installierte Updateschutz ist unvollständig. Stelle die Dateien der zuletzt installierten freigegebenen Version wieder her.';
$string['updateguard_operation'] = 'Diese Kombination von Installationsaktionen wird nicht unterstützt. Starte einen einzelnen Updatevorgang erneut.';
$string['updateguard_failed'] = 'Das Update konnte nicht geprüft werden. Es wurde keine Installation gestartet.';
$string['updateguard_back'] = 'Zur Pluginübersicht';
// End shared independent signed update protection.

$string['enrolperiod'] = 'Dauer des Kurszugangs';

$string['enrolperiod_help'] = ('Ohne Aktivierung hat der Zugang kein geplantes Enddatum. Ein befristeter '
    . 'Zeitraum beginnt bei der Auslieferung des Kurszugangs. Änderungen gelten '
    . 'für zukünftige Bestellungen; gespeicherte Bestellungen behalten ihre '
    . 'ursprüngliche Dauer.');

$string['unlimitedaccess'] = 'Ohne festes Enddatum';
$string['gateway_buy'] = 'Kurszugang kaufen';
$string['gateway_oneoff'] = 'Einmalzahlung. Der Zugang beginnt mit der Freischaltung; die Dauer steht beim jeweiligen Kurs.';
$string['gateway_subscription'] = 'Abonnement. Zahlungsintervall und Bedingungen stehen im Checkout.';
$string['gateway_return'] = 'Weiter zu Deinem Kurs';
