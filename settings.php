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
 * moodlecst module admin settings and defaults
 *
 * @package    mod
 * @subpackage moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/mod/moodlecst/lib.php');

if ($ADMIN->fulltree) {



	 $settings->add(new admin_setting_configtext('mod_moodlecst/nodejsport',
        get_string('nodejsport', 'moodlecst'), get_string('nodejsport_details', MOD_MOODLECST_LANG), 8082, PARAM_INT));

		
	$settings->add(new admin_setting_configtext('mod_moodlecst/nodejsapppath',
        get_string('nodejsapppath', 'moodlecst'), get_string('nodejsapppath_details', MOD_MOODLECST_LANG), 'cst/app.js' , PARAM_RAW));
		
	//General Instructions at beginning of activity
	$defaultInstructions =	"<h1>スピーキング伝達能力テストのやり方<br /></h1>";
	$defaultInstructions .= "<p>これは英語スピーキング伝達能力テストです。これからスクリーンに、あなたが相手に説明するための情報が表示されます。";
	$defaultInstructions .= "その情報は単語や絵またはオーディオクリップです。あなたの目的は、相手にあなたが得た情報を";
	$defaultInstructions .= "<strong>英語で出来るだけ速く</strong>伝えることです。準備が出来たら、下の「Start」ボタンをタッチして始めて下さい。</p>";
	$defaultInstructions .= "<p>&nbsp;</p><h1>Communicative Speaking Test Instructions</h1>";
	$defaultInstructions .= "<p>This is a test of communicative English speaking. You will be presented with information to explain to your partner.";
	$defaultInstructions .= "This information may be a word, a picture, or an audio clip. Your goal is to explain the material";
	$defaultInstructions .= "<strong> in English </strong><em><strong>as quickly as possible.</strong></em> ";
	$defaultInstructions .= "When you are ready, touch the &quot;Start&quot; button below.</p>";
	
	 $settings->add(new admin_setting_configtextarea('mod_moodlecst/generalinstructions_teacher',
				get_string('generalinstructions_teacher', MOD_MOODLECST_LANG),
				get_string('generalinstructions_teacher_desc', MOD_MOODLECST_LANG),$defaultInstructions));
	
	$defaultInstructions =	"<h1>スピーキング伝達能力テスト<br /></h1>";
	$defaultInstructions .= "<p>パートナの指示通りにそれぞれの問題を答えてください。</p>";
	$defaultInstructions .= "<h1>Speaking Activity/Test</h1>";
	$defaultInstructions .= "<p>Please answer each question according to your partner's explanation.";
	$settings->add(new admin_setting_configtextarea('mod_moodlecst/generalinstructions_student',
				get_string('generalinstructions_student', MOD_MOODLECST_LANG),
				get_string('generalinstructions_student_desc', MOD_MOODLECST_LANG),$defaultInstructions));
	
	$defaultConsent ="<h1>研究参加同意書 </h1><h1>Project: 英語スピーキング伝達能力客観テスト</h1>";
	$defaultConsent .="<h2> 研究目的 / Purpose of the Study: </h2>
<p>本研究は英語スピーキング伝達能力のコンピューター適応型テストの提案をするものである。また本研究は信頼性が高い学力検査の評価を掲示することで試験の信頼度を増やして利用者のコストを減らす事を目指すものである。提案する試験では、出題者と回答者はアップルiPadを持ちます。各iPadはお互いがタスクを行う際、違う資料を表示します。出題者と回答者は共同してタスクを終了します。この研究の目的はコンピューター適応型テストの作成のために、試験の有効性を精査して、各項目のパラメーター推計を計算することです。テストのセッションはビデオカメラで録画される可能性もあります。</p>
<p>The researchers propose a computer adaptive test of speaking ability for the purposes of improving test reliability and increasing the cost-effectiveness of speaking tests by delivering reliable assessments in less time. In the proposed test, a tester and an examinee each have an iPad, which displays different information necessary to complete a task. They must collaborate to complete the task. The goal of this research is to validate the efficacy of such a format, and obtain parameter estimates for items for use in a computer adaptive test employing the format. Test sessions may be videotaped.</p>
<h2>あなたの権利 / Your Rights: </h2>
<h3>個人情報保護の権利 / Right to protection of personal information </h3>
<p>この研究で得られた情報は研究者のみが所持し、あなたの個人情報が漏洩されることはありません。あなたが提供されるデータに個別に言及がなされる場合には、偽名（仮名）が与えられることになります。 </p>
<p>The data gathered in this research are to be owned by the above researchers only. There will be no way whatsoever of your personal information being leaked to any other party. In cases of the data you provide being dealt with individually in written or oral presentation, you will be referred to by a pseudonym. </p>
<h3>質問する権利 / Right to inquiry </h3>
<p>この研究についてご質問があればいつでも研究者に尋ねてください。Ｅメールの場合は REDACTED:AARON_BATTY_EMAIL かREDACTED:JEFF_STEWARTEMAIL まで、また研究者の研究室まで直接おいでいただいても構いません。 </p>
<p>If you have questions about this research, please ask the researcher at any time. If you would like to ask via email, please write to REDACTED:AARON_BATTY_EMAIL or REDACTED:JEFF_STEWARTEMAIL, or you may come directly to one of the researchers offices. </p>
<h3>研究への参加を取りやめる権利 / Right to end participation </h3>
<p>あなたはいつでもこの研究への参加を取りやめることができ、ご希望があればデータを処分いたします。 </p>
<p>You have the right to opt out of your participation in this research at any time. The data you provide will be deleted upon your request. </p>
<h3>研究における危険性 / Risks associated with this research </h3>
<p>この研究において危険を伴うような作業は一切いたしません。 </p>
<p>There is no dangerous task or procedure to go through in this research. </p>
<hr />
<p>私は以上の内容を読んで理解し、この研究に関して不明な点はすべて十分な説明を受けました。また自分に不都合が生じることなくこの研究への参加をいつでも取りやめることができることを理解しました。 私は、自分が有する権利を失うことなく、また研究者に過失があった場合には研究者自身がその責任を負うことを理解した上でこの研究に参加することに同意します。 </p>
<p>I have read and understood the above, and have been provided enough explanation as to whatever points might have been unclear about this research. I also understand that I have the right to end my participation in this research at any time, with no consequences. With the understanding that any fault in the research is solely the responsibility of the researchers, and not of mine, I agree to participate in this research. </p>";
	$settings->add(new admin_setting_configtextarea('mod_moodlecst/consent',
				get_string('consent', MOD_MOODLECST_LANG),
				get_string('consent_desc', MOD_MOODLECST_LANG),$defaultConsent));

}
