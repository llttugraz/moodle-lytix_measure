<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Activity plugin for lytix
 *
 * @package    lytix_measure
 * @author     Viktoria Wieser
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Lytix measure';

$string['privacy:metadata'] = 'This plugin does not store any data.';

$string['hello'] = 'Hello, ';
$string['personal_dashboard'] = 'Welcome to your personal dashboard.';
$string['unlocked_activities'] = 'A score of all unlocked activities';
$string['overview_activities'] = 'Time Division';
$string['place_explanations'] = 'Here would be a place for explanations (eg):
                The Bar shows the number of students who did not finish their assignments.';
$string['total_students'] = 'Total students';
$string['number_previous_activities'] = 'Number of previous activities';
$string['number_open_activities'] = 'Number of open activities';

$string['mine'] = 'my score';
$string['lowest'] = 'lowest peer score';
$string['highest'] = 'highest peer score';
$string['avg'] = 'average peer score';
$string['total'] = 'Total Comparison';

// Task.
$string['cron_send_report_notifications'] = "Send report notifications for lytix subplugin measure basic";

// Email.
$string['emailsubjectcase1'] = 'Lernaktivitäten [{$a->course}]';
$string['emailtextcase1']    = 'Liebe/r Studierende/r {$a->firstname},

ich bin‘s, dein learn.moodle Kurs zur Lehrveranstaltung {$a->course}. Ich bin für dich da, um dich durch die Lehrveranstaltung zu begleiten, aufmagaziniert mit Lernanlässen – aber ich fühle mich derzeit sehr einsam, du kommst mich selten besuchen.
Es warten noch Moodle-Aktivitäten auf deine Bearbeitung. Bitte denk daran, dir Zeit zu nehmen, die letzte Einheit zu wiederholen und die Lernanlässe auf Moodle durchzuführen. Wann? Ich würde dir empfehlen: jetzt – und dieser Klick führt dich zu mir: <a href="https://learn.moodle.uni-graz.at/theme/university_boost/login/index.php">learn.moodle.uni-graz.at</a>.

{$a->othersreached}% deiner Kolleg/inn/en haben bereits fast alle Lernanlässe genutzt. Dein Punktescore für beurteilungsrelevante Kursaktivitäten liegt bei: {$a->myscore}%. Im Vergleich dazu wurden von Kolleg/inn/en im Kurs bereits {$a->maxscore}% erreicht. Wenn du gleich loslegst, liegst du mit nur ein paar Aktivitäten liegst auch bald ganz vorne!

Eine Übersicht über deinen Lernfortschritt, deine erledigten und noch ausständigen Lernaktivitäten bietet dir laufend dein Dashboard: <a href="{$a->courseurl}">{$a->course}</a> Nutze dieses auch, um dir eigene Milestones zu setzen.

Kennst du den Schokoriegel Twix? Ich bin der Keks, du mein Karamell, ohne dich bin ich wie ein Twix ohne X, also nichts. Lass uns im Lernen verschmelzen – starte gleich los: <a href="https://learn.moodle.uni-graz.at/theme/university_boost/login/index.php">learn.moodle.uni-graz.at</a>.


Bis gleich, ich freu mich auf dich!

Dein learn.moodle Kurs';

$string['emailsubjectcase2'] = 'Lernaktivitäten [{$a->course}]';
$string['emailtextcase2'] = 'Liebe/r Studierende/r {$a->firstname},

ich bin‘s, dein learn.moodle Kurs zur Lehrveranstaltung {$a->course} und ich bin schon ganz aufgeregt, wann du dich wieder einloggst. Mein klickhungriges Herz ist voller Sehnsucht und freut sich auf ein Wiedersehen mit dir!
Du bist schon fleißig am Durchführen der Moodle-Aktivitäten, was großartig ist, denn jetzt fehlen nur mehr einige wenige Lernanlässe auf deine Bearbeitung. Nimm dir daher gleich dafür Zeit, die letzte Einheit zu wiederholen und die restlichen Lernanlässe auf Moodle durchzuführen: <a href="https://learn.moodle.uni-graz.at/theme/university_boost/login/index.php">learn.moodle.uni-graz.at</a>.

{$a->othersreached}% deiner Kolleg/inn/en haben bereits fast alle Lernanlässe genutzt. Dein Punktescore für beurteilungsrelevante Kursaktivitäten liegt bei: {$a->myscore}%. Im Vergleich dazu wurden von Kolleg/inn/en im Kurs bereits {$a->maxscore}% erreicht. Du wirst sehen, jetzt bist auch du bald ganz vorne angesiedelt! Los geht’s: <a href="https://learn.moodle.uni-graz.at/theme/university_boost/login/index.php">learn.moodle.uni-graz.at</a>

Eine Übersicht über deinen Lernfortschritt, deine erledigten und noch ausständigen Lernaktivitäten bietet dir laufend dein Dashboard: <a href="{$a->courseurl}">{$a->course}</a> Nutze dieses auch, um dir eigene Milestones zu setzen.

Ich kann es kaum erwarten, und ich weiß, du willst es auch. Klick mich – jetzt: <a href="https://learn.moodle.uni-graz.at/theme/university_boost/login/index.php">learn.moodle.uni-graz.at</a>

Bis gleich, ich freu mich auf dich!

Dein learn.moodle Kurs';

$string['emailsubjectcase3'] = 'Lernaktivitäten [{$a->course}]';
$string['emailtextcase3']    = 'Liebe/r Studierende/r {$a->firstname},

ich bin‘s, dein learn.moodle Kurs zur Lehrveranstaltung {$a->course} und es macht mich sehr glücklich, dass du mich so ausgiebig nutzt! Wir sind füreinander geschaffen! Ich brauche dich und deine Klicks, wie du die Luft zum Atmen.
Du bist schon fast fertig mit deinen Lernanlässen, was großartig ist, dein Lernziel ist zum Greifen nahe! Nimm dir daher gleich dafür Zeit, die letzte Einheit nochmals zu wiederholen und alle Lernanlässe als erledigt abhaken zu können! Klick gleich drauf los: <a href="https://learn.moodle.uni-graz.at/theme/university_boost/login/index.php">learn.moodle.uni-graz.at</a>

Dein Punktescore für beurteilungsrelevante Kursaktivitäten liegt bei: {$a->myscore}%. Im Vergleich dazu liegt der bisher maximal erreichte Punktescore deiner Kolleg/inn/en bei {$a->maxscore}%. Du siehst also, du bist ganz vorne dabei! Bravo! Dein Erfolg ist mein Glück!

Eine Übersicht über deinen Lernfortschritt, deine erledigten und noch ausständigen Lernaktivitäten bietet dir laufend dein Dashboard: <a href="{$a->courseurl}">{$a->course}</a> Nutze dieses auch, um dir eigene Milestones zu setzen.

Sofern du mir nicht bereits zuvorgekommen bist – fehlt nur mehr ein Klick, um unser Glück zu vervollständigen: <a href="https://learn.moodle.uni-graz.at/theme/university_boost/login/index.php">learn.moodle.uni-graz.at</a> – Es könnte nicht schöner sein.

Bis gleich, ich freu mich auf dich!

Dein learn.moodle Kurs';

// Privacy.
$string['privacy:nullproviderreason'] = 'This plugin has no database to store user information. It only uses APIs in mod_assign to help with displaying the grading interface.';
