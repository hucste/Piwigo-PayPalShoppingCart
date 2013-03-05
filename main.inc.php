<?php
/*
Plugin Name: PayPal Shopping Cart
Version: auto
Description: Append PayPal Shopping Cart on Piwigo to sell photos
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=499
Author: queguineur.fr
Author URI: http://www.queguineur.fr
*/
/*
  Plugin Panier PayPal Pour Piwigo
  Copyright (C) 2011 www.queguineur.fr � Tous droits r�serv�s.
  
  Ce programme est un logiciel libre ; vous pouvez le redistribuer ou le
  modifier suivant les termes de la �GNU General Public License� telle que
  publi�e par la Free Software Foundation : soit la version 3 de cette
  licence, soit (� votre gr�) toute version ult�rieure.
  
  Ce programme est distribu� dans l�espoir qu�il vous sera utile, mais SANS
  AUCUNE GARANTIE : sans m�me la garantie implicite de COMMERCIALISABILIT�
  ni d�AD�QUATION � UN OBJECTIF PARTICULIER. Consultez la Licence G�n�rale
  Publique GNU pour plus de d�tails.
  
  Vous devriez avoir re�u une copie de la Licence G�n�rale Publique GNU avec
  ce programme ; si ce n�est pas le cas, consultez :
  <http://www.gnu.org/licenses/>.
*/
/*
Historique
1.0.0   10/02/2011
Version initiale
		
1.0.1   10/02/2011
Ajout du Plugin URI pour permettre les mises � jours
Traduction en Anglais du Plugin Name et du nom du r�pertoire
        
1.0.2   10/02/2011
Correction du probl�me de compatibilit� avec exif view (double affichage des boutons)
	
1.0.3   15/02/2011
Add lv_LV (Latvian) thanks to Aivars Baldone

1.0.4   17/02/2011
Add de_DE and it_IT (par Sugar888)

1.0.5   27/02/2011
Correction pb compatibilit� avec certains th�mes
D�placement des boutons PayPal en d�but de table info

1.0.6   05/03/2011
Add sk_SK (by dodo)

1.0.7   26/03/2011
Add hu_HU language (Hungarian) thanks to samli

*/
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $prefixeTable;

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+

defined('PPPPP_ID') or define('PPPPP_ID', basename(dirname(__FILE__)));
define('PPPPP_PATH' , PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/');
define('PPPPP_SIZE_TABLE', $prefixeTable.'ppppp_size');
define('PPPPP_CONFIG_TABLE', $prefixeTable.'ppppp_config');
define('PPPPP_VERSION', 'auto');


function ppppp_append_form($tpl_source, &$smarty){
 $pattern = '#<.*\"infoTable\".*>#';
 $replacement = '
  <tr>
   <td class="label">{\'Buy this picture\'|@translate}</td>
   <td>
    <form name="ppppp_form" target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post" onSubmit="javascript:pppppValid()">
     <input type="hidden" name="add" value="1">
     <input type="hidden" name="cmd" value="_cart">
     <input type="hidden" name="business" value="{$ppppp_e_mail}">
     <input type="hidden" name="item_name">
     <input type="hidden" name="no_shipping" value="2"><!-- shipping address mandatory -->
	 <input type="hidden" name="handling_cart" value="{$ppppp_fixed_shipping}"> 
     <input type="hidden" name="currency_code" value="{$ppppp_currency}">
     <select name="amount">
	  {foreach from=$ppppp_array_size item=ppppp_row_size}
      <option value="{$ppppp_row_size.price}">{$ppppp_row_size.size} : {$ppppp_row_size.price} {$ppppp_currency}</option>
	  {/foreach}
     <input type="submit" value="{\'Add to cart\'|@translate}">
    </form>
   </td>
   <td>
    <form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
     <input type="hidden" name="cmd" value="_cart">
     <input type="hidden" name="business" value="{$ppppp_e_mail}">
     <input type="hidden" name="display" value="1">
     <input type="hidden" name="no_shipping" value="2">
     <input type=submit value="{\'View Shopping Cart\'|@translate}">
    </form>
   </td>
  </tr> 
 
 {literal}
 <script type="text/javascript">
 function pppppValid(){
  var amount=document.ppppp_form.amount;
  var selectedAmount=amount[amount.selectedIndex];
  document.ppppp_form.item_name.value="Photo \"{/literal}{$current.TITLE}\", Ref {$INFO_FILE}, {\'Size\'|@translate} : {literal} "+selectedAmount.text;
  }
 </script>
 {/literal}
 ';
 if(!preg_match($pattern,$tpl_source)) {
  $pattern='#{if isset\(\$COMMENT_IMG\)}#';
  $replacement='<table>'.$replacement.'</table>';
  $replacement=$replacement.'$0';
  }
 else
  $replacement='$0'.$replacement;
 return preg_replace($pattern, $replacement, $tpl_source,1);
 }

function ppppp_picture_handler(){
  global $template, $conf, $page;

  if ($conf['PayPalShoppingCart']['apply_to_albums'] == 'list')
  {
    if (!isset($page['category']))
    {
      return;
    }

    $query = '
SELECT
    paypal_active
  FROM '.CATEGORIES_TABLE.'
  WHERE id = '.$page['category']['id'].'
;';
    list($paypal_active) = pwg_db_fetch_row(pwg_query($query));

    if ('false' == $paypal_active)
    {
      return;
    }
  }   
 
 $template->set_prefilter('picture', 'ppppp_append_form');
 load_language('plugin.lang', PPPPP_PATH);
 $query='SELECT * FROM '.PPPPP_SIZE_TABLE.' '.@$conf['PayPalShoppingCart_sizes_order_by'].';';
 $result = pwg_query($query);
 while($row = pwg_db_fetch_assoc($result)){
  $template->append('ppppp_array_size',$row);
  }
 $query='SELECT value FROM '.PPPPP_CONFIG_TABLE.' WHERE param = \'fixed_shipping\';';
 $result = pwg_query($query);
 $row = pwg_db_fetch_row($result);
 $template->assign('ppppp_fixed_shipping',$row[0]); 
 $query='SELECT value FROM '.PPPPP_CONFIG_TABLE.' WHERE param = \'currency\';';
 $result = pwg_query($query);
 $row = pwg_db_fetch_row($result);
 $template->assign('ppppp_currency',$row[0]);
 
 $template->assign('ppppp_e_mail',get_webmaster_mail_address());
 }

add_event_handler('loc_begin_picture', 'ppppp_picture_handler');

function ppppp_append_js($tpl_source, &$smarty){
 load_language('plugin.lang', PPPPP_PATH);
 if(strstr($tpl_source,"{'Menu'|@translate}")==false)
  return $tpl_source;
 $pattern = '#{/foreach}#';  
 $replacement = '{/foreach}
 <li><a href="" title="'.l10n('View my PayPal Shopping Cart').'" onclick="document.forms[\'ppppp_form_view_cart\'].submit()">'.l10n('View Shopping Cart').'</a></li>
 <form name="ppppp_form_view_cart" target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
     <input type="hidden" name="cmd" value="_cart">
     <input type="hidden" name="business" value="{$ppppp_e_mail}">
     <input type="hidden" name="display" value="1">
     <input type="hidden" name="no_shipping" value="2">
  </form>
  ';
 return preg_replace($pattern, $replacement, $tpl_source); 
 }

function ppppp_index_handler(){
 global $template;
 $template->set_prefilter('menubar', 'ppppp_append_js');
 $template->assign('ppppp_e_mail',get_webmaster_mail_address()); 
 }

add_event_handler('loc_begin_index', 'ppppp_index_handler');

function ppppp_admin_menu($menu){
 load_language('plugin.lang', PPPPP_PATH);
 array_push($menu, array(
  'NAME' => l10n('PayPal Shopping Cart'),
  'URL' => get_admin_plugin_menu_link(PPPPP_PATH . 'admin.php')));
 return $menu;
 }

add_event_handler('get_admin_plugin_menu_links', 'ppppp_admin_menu');

add_event_handler('init', 'ppppp_init');
/**
 * plugin initialization
 *   - check for upgrades
 *   - unserialize configuration
 *   - load language
 */
function ppppp_init()
{
  global $conf, $pwg_loaded_plugins;
  
  // apply upgrade if needed
  if (
    PPPPP_VERSION == 'auto' or
    $pwg_loaded_plugins[PPPPP_ID]['version'] == 'auto' or
    version_compare($pwg_loaded_plugins[PPPPP_ID]['version'], PPPPP_VERSION, '<')
  )
  {
    // call install function
    include_once(PPPPP_PATH.'include/install.inc.php');
    ppppp_install();
    
    // update plugin version in database
    if ( $pwg_loaded_plugins[PPPPP_ID]['version'] != 'auto' and PPPPP_VERSION != 'auto' )
    {
      $query = '
UPDATE '. PLUGINS_TABLE .'
SET version = "'. PPPPP_VERSION .'"
WHERE id = "'. PPPPP_ID .'"';
      pwg_query($query);
      
      $pwg_loaded_plugins[PPPPP_ID]['version'] = PPPPP_VERSION;
      
      if (defined('IN_ADMIN'))
      {
        $_SESSION['page_infos'][] = 'PayPalShoppingCart plugin updated to version '. PPPPP_VERSION;
      }
    }
  }
  
  // load plugin language file
  load_language('plugin.lang', PPPPP_PATH);
  
  // prepare plugin configuration
  $conf['PayPalShoppingCart'] = unserialize($conf['PayPalShoppingCart']);
}
?>