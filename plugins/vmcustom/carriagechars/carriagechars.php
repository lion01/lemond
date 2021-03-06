<?php
defined('_JEXEC') or 	die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' ) ;
/**
 * @version 1.0b
 *
 * a special type of 'product characteristics':
 * @author Akchurin V
 *
 * http://virtuemart.org
 */

if (!class_exists('vmCustomPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmcustomplugin.php');

class plgVmCustomCarriageChars extends vmCustomPlugin {


	function __construct(& $subject, $config) {

		parent::__construct($subject, $config);

		$this->_tablepkey = 'id';
		$this->tableFields = array_keys($this->getTableSQLFields());
		$this->varsToPush = array(
			'country'=> array('', 'string'),
			'age'=> array('', 'string'),
			'type'=> array('', 'string'),
			'gender'=> array('', 'char'),
			'brend' => array('', 'string')
		);

		$this->setConfigParameterable('custom_params',$this->varsToPush);

	}
	/**
	 * Create the table for this plugin if it does not yet exist.
	 * @author Val�rie Isaksen
	 */
	public function getVmPluginCreateTableSQL() {
		return $this->createTableSQL('Product Specification Table');
	}

	function getTableSQLFields() {
		$SQLfields = array(
	    'id' => 'int(11) unsigned NOT NULL AUTO_INCREMENT',
	    'virtuemart_product_id' => 'int(11) UNSIGNED DEFAULT NULL',
	    'virtuemart_custom_id' => 'int(11) UNSIGNED DEFAULT NULL',
	    'country' => 'int(5) NOT NULL DEFAULT 0 ',
	    'age' => 'int(5) NOT NULL DEFAULT 0 ',
	    'type' => 'int(5) NOT NULL DEFAULT 0 ',
	    'gender'=> 'int(1) NOT NULL DEFAULT 0 ',
	    'brend'=> 'int(5) NOT NULL DEFAULT 0 ',
		);

		return $SQLfields;
	}
	
	/*
	 * Get the minimal and maximal prices in category
	 */
	 private function getPriceLimits()
	 {
	 	$db =JFactory::getDBO();
		$query='SELECT max(pp.`final_price`) AS maxPrice, min(pp.`final_price`) AS minPrice FROM ' .
			   '#__virtuemart_product_categories as pc RIGHT JOIN #__virtuemart_products as p ' .
			   'ON pc.`virtuemart_product_id` = p.`virtuemart_product_id` ' .
			   'RIGHT JOIN #__virtuemart_product_prices as pp ON ' .
			   'p.`virtuemart_product_id` = pp.`virtuemart_product_id`' .
			   'WHERE pc.`virtuemart_category_id`='.JRequest::getVar('virtuemart_category_id',0);
		$db->setQuery($query);
		return $db->loadObject();
	 }
	 
	 /*
	  * Gets filter elements in unfiltered category
	  */
	 private function getSimpleSearchVols()
	 {
	 	$db=& JFactory::getDBO();
	 	$search_vols=array();
		foreach($this->_varsToPushParam as $k=>$v)
		{
			$db->setQuery('SELECT DISTINCT pcnt.id AS id, content ' .
						  'FROM #__virtuemart_product_custom_plg_carriage_cats AS pcc ' .
						  'RIGHT JOIN #__virtuemart_product_custom_plg_carriage_content AS pcnt ON ' .
						  'pcnt.id = pcc.content_id ' .
						  'WHERE pcc.virtuemart_category_id='.JRequest::getVar('virtuemart_category_id',0).' ' .
						  'AND pcnt.col_name="'.$k.'"');
			$search_vols[$k]=$db->loadObjectList();
		}
		return $search_vols;
	 }
	 
	 /*
	  * Gets filter elements in filtered categoty
	  */
	 private function getFilteredSearchVols($virtuemart_custom_id)
	 {
	 	$query='SELECT DISTINCT pcc.id AS id, content ' .
				'FROM #__virtuemart_product_prices AS pp LEFT JOIN ' .
				'#__virtuemart_product_custom_plg_carriagechars AS carriagechars ON ' .
				'carriagechars.virtuemart_product_id=pp.virtuemart_product_id ' .
				'LEFT JOIN #__virtuemart_product_categories as pc ON ' .
				'pp.virtuemart_product_id = pc.virtuemart_product_id ' .
				'LEFT JOIN #__virtuemart_product_custom_plg_carriage_content as pcc ON ' .
				'pcc.id=carriagechars.|vol| ' .
				'WHERE ';
		$where=array();
		$temp=null;
		$this->plgVmAddToSearch($where,$temp,$virtuemart_custom_id);
		$where[]=' (pp.product_price_publish_up = "0000-00-00 00:00:00" OR ' .
				 '(NOW() BETWEEN pp.product_price_publish_up AND pp.product_price_publish_down)) ';
		$category=&JRequest::getVar('virtuemart_category_id',0);
		if($category!=0)
			$where[]='(pc.virtuemart_category_id='.$category.')';
		$query.=implode(' AND ',$where);
		$db=& JFactory::getDBO();
		foreach($this->_varsToPushParam as $k=>$v)
		{
			 $db->setQuery(str_replace('|vol|',$k,$query));
			// print(str_replace('#_','lem',str_replace('|vol|',$k,$query))); die();
			 $search_vols[$k]=$db->loadObjectList();			
		}
		return $search_vols;
		
	 }

	/*
	*
	* Render the search in category
	* @ $selectList the list contain all the possible plugin(+customparent_id)
	* @ &$searchCustomValues The HTML to render as search fields
	*
	*/
	public function plgVmSelectSearchableCustom(&$selectList,&$searchCustomValues,$virtuemart_custom_id)
	{
		if ($this->_name != $this->GetNameByCustomId($virtuemart_custom_id)) 
		{
			//print($this->_name.' != '.$this->GetNameByCustomId($virtuemart_custom_id));
			return true;
		}
		
		$search_vols=array();
		if(JRequest::getVar('custom_parent_id',0)==$virtuemart_custom_id)
		{
			$search_vols=$this->getFilteredSearchVols($virtuemart_custom_id);
		}
		else
		{
			$search_vols=$this->getSimpleSearchVols();
		}
		
		$db=&JFactory::getDBO();		
		$priceLimits=$this->getPriceLimits();
		
		$doc=&JFactory::getDocument();
		$doc->addStyleSheet('plugins/vmcustom/carriagechars/slider/css/jslider.css');
		$doc->addStyleSheet('plugins/vmcustom/carriagechars/slider/css/jslider.plastic.css');
		//$doc->addScript('plugins/vmcustom/carriagechars/slider/js/jquery-1.7.1.js');
		$doc->addScript('plugins/vmcustom/carriagechars/slider/js/jshashtable-2.1_src.js');
		$doc->addScript('plugins/vmcustom/carriagechars/slider/js/jquery.numberformatter-1.2.3.js');
		$doc->addScript('plugins/vmcustom/carriagechars/slider/js/tmpl.js');
		$doc->addScript('plugins/vmcustom/carriagechars/slider/js/jquery.dependClass-0.1.js');
		$doc->addScript('plugins/vmcustom/carriagechars/slider/js/draggable-0.1.js');
		$doc->addScript('plugins/vmcustom/carriagechars/slider/js/jquery.slider.js');
		$doc->addScriptDeclaration
		(
			'jQuery.noConflict();' .
			'jQuery(document).ready(function(){' .
				'jQuery("#slider-price").slider({ ' .
					'from: '.$priceLimits->minPrice.', to: '.$priceLimits->maxPrice.', ' .
					'limits: false, ' .
					'step: 1, ' .
					'skin: "plastic" });' .
				'jQuery("#slider-price").slider().update();' .
			'});'
		);
		
		ob_start();
		include(JPATH_SITE.DS.'plugins/vmcustom/carriagechars/tmpl/default.php');
		$searchCustomValues.=ob_get_clean();
		return true;
	}
	
	/*
	 * Returns part of WHERE clause in filter request
	 * @ $array array of variants choosen by user
	 * @ $colName name of column to filter
	 */
	private function addWherePart($array,$colName)
	{
		$wherePart=array();
		if($array == null) return null;
		foreach($array as $item)
			$wherePart[]=$colName.' = '.$item.'';
		$res='('.implode($wherePart,' OR ').')';
		if($res!='()')
			return $res;
		return null;		
	}
	
	/*
	* Extend the search in category
	* @ $where the list contain all the possible plugin(+customparent_id)
	* @ $PluginJoinTables The plg_name table to join on the search
	* (in normal case must be = to $this->_name)
	*/
	public function plgVmAddToSearch(&$where,&$PluginJoinTables,$custom_id)
	{
		if ($this->_name != $this->GetNameByCustomId($custom_id)) return;
		$db =JFactory::getDBO();
		$query='SELECT max(pp.`product_price`) AS maxPrice, min(pp.`product_price`) AS minPrice FROM ' .
			   '#__virtuemart_product_categories as pc RIGHT JOIN #__virtuemart_products as p ' .
			   'ON pc.`virtuemart_product_id` = p.`virtuemart_product_id` ' .
			   'RIGHT JOIN #__virtuemart_product_prices as pp ON ' .
			   'p.`virtuemart_product_id` = pp.`virtuemart_product_id`' .
			   'WHERE pc.`virtuemart_category_id`='.JRequest::getVar('virtuemart_category_id',0);
		$db->setQuery($query);
		$priceLimits=$db->loadObject();
		
		$price=JRequest::getVar('price', null, ' ');
		if($price!=null) {list($minPrice,$maxPrice)=explode(';',$price); }

		$country=JRequest::getVar('country', null, ' ');
		$brend=JRequest::getVar('brend', null, ' ');
		$age=JRequest::getVar('age', null, ' ');
		$type=JRequest::getVar('type', null, ' ');
		$gender=JRequest::getVar('gender', null, ' ');

		if($price!=null)
			$where[]='(pp.`final_price` BETWEEN '.$minPrice.' AND '.$maxPrice.')';
		if($wherePart=$this->addWherePart($brend,$this->_name.'.`brend`'))
			$where[]=$wherePart;
		if($wherePart=$this->addWherePart($country,$this->_name.'.`country`'))
			$where[]=$wherePart;
		if($wherePart=$this->addWherePart($age,$this->_name.'.`age`'))
			$where[]=$wherePart;
		if($wherePart=$this->addWherePart($type,$this->_name.'.`type`'))
			$where[]=$wherePart;
		if($wherePart=$this->addWherePart($gender,$this->_name.'.`gender`'))
			$where[]=$wherePart;
		
		$PluginJoinTables[0]=$this->_name;
		return true;
	}
	
	/*
	 * override of getPluginCustomData because of virtuemart bug with string items
	 * gets values of carriage characteristics
	 * @ $field field param passed to plgVmOnProductEdit
	 * @ $product_id id of carriage to process
	 */
	protected function my_getPluginCustomData (&$field, $product_id) 
	{
		$id = $this->getIdForCustomIdProduct ($product_id, $field->virtuemart_custom_id);
		$datas = $this->getPluginInternalData ($id);
		$db=&JFactory::getDBO();
		if ($datas) {
			foreach ($this->_varsToPushParam as $k => $v) {
				if (!isset($datas->$k)) {
					continue;
				}
				$db->setQuery('SELECT `content` FROM #__virtuemart_product_custom_plg_carriage_content ' .
							  'WHERE `id`='.$datas->$k);
				$this->params->$k = $db->loadResult();
			}
		}
	}
        
        private function getAllOptions()
        {
            $vols=array('country','age','type','brend');
            $db=&JFactory::getDBO();
            foreach($vols as $vol){
                $query='SELECT DISTINCT content as id, content as val FROM #__virtuemart_product_custom_plg_carriagechars as cpc
                    LEFT JOIN #__virtuemart_product_custom_plg_carriage_content as cpcc
                    ON cpc.'.$vol.'=cpcc.id';
                $db->setQuery($query);
                $this->params->options[$vol]=$db->loadAssocList();
            }
        }

	/*
	 * Renders html while editing probuct in backend
	 * @ $field all about product
	 * @ $product_id id of product
	 * @ $row number of row in table, in editig page
	 * @ $retValue rendered HTML
	 */
	function plgVmOnProductEdit($field, $product_id, &$row,&$retValue) {
		if ($field->custom_element != $this->_name) return '';

		$this->getCustomParams($field);
		$this->my_getPluginCustomData($field, $product_id);
                $this->getAllOptions();
                $doc=  &JFactory::getDocument();
                $doc->addScript(JURI::base(true).'/../plugins/vmcustom/carriagechars/tmpl/charsback.js');

		$html ='<div>';
                $vols=array('country'=>'Страна','age'=>"Возраст",'type'=>"Тип",'brend'=>'Бренд');
                foreach ( $vols as $vol=>$key ){
                    $html .='<br>'.$key.'<br>';
                    $html.='<input type="checkbox" id="'.$vol.'checker" name="'.$vol.'manualVal" value="1">Другой вариант';
                    $html.='<div id="'.$vol.'list">'.JHTML::_('select.genericlist',
                                                            $this->params->options[$vol],
                                                            'plugin_param['.$row.']['.$this->_name.']['.$vol.']',
                                                            null,
                                                            'id','val',
                                                            $this->params->$vol,
                                                            null,
                                                            false);
                    $html .='</div><br><input type="text" value="" id="'.$vol.'manual" size="10" name="asd" style="display:none">';
                    $doc->addScriptDeclaration('
                        jQuery(document).ready(function(){
                            jQuery("#'.$vol.'checker").manualChoose({"manual-selector":"#'.$vol.'manual",
                                "list-selector":"#'.$vol.'list",
                                "name":"plugin_param['.$row.']['.$this->_name.']['.$vol.']"});
                        });
                    ');
                }
		$options[]=JHTML::_('select.option','Мальчик','Мальчик');
		$options[]=JHTML::_('select.option','Девочка','Девочка');
                $options[]=JHTML::_('select.option','Любой','Любой');
                $html .='<br>Пол ребенка ';
		$html.=JHTML::_('select.genericlist',
							$options,
							'plugin_param['.$row.']['.$this->_name.'][gender]',
							null,
							'value','text',
							$this->params->gender,
							null,
							false);
		$html .='<input type="hidden" value="'.$this->virtuemart_custom_id.'" name="plugin_param['.$row.']['.$this->_name.'][virtuemart_custom_id]">';
		$html .='</div>';
		$retValue .= $html  ;
		$row++;
		return true  ;
	}

	/*
	 * @ idx plugin index
	 * @see components/com_virtuemart/helpers/vmCustomPlugin::onDisplayProductFE()
	 * @author Patrick Kohl
	 *  Display product
	 */
	function plgVmOnDisplayProductFE($product,&$idx,&$group) {
		// default return if it's not this plugin
		if ($group->custom_element != $this->_name) return '';

		$this->_tableChecked = true;
		$this->getCustomParams($group);
		$this->my_getPluginCustomData($group, $product->virtuemart_product_id);

		// Here the plugin values
		//$html =JTEXT::_($group->custom_title) ;
                
                $html='<div class="chars-header">Характеристики</div>'.
                        "<div class=\"chars-content\"><div class=\"chars-row grey\"><span class=\"chars-name\">Тип:</span><span class=\"chars-cont\">".$this->params->type."</span></div>".
                        "<div class=\"chars-row\"><span class=\"chars-name\">Возраст:</span><span class=\"chars-cont\">".$this->params->age."</span></div>".
                        "<div class=\"chars-row grey\"><span class=\"chars-name\">Страна-производитель:</span><span class=\"chars-cont\">".$this->params->country."</span></div>".
                        "<div class=\"chars-row\"><span class=\"chars-name\">Бренд:</span><span class=\"chars-cont\">".$this->params->brend."</span></div></div>";
                

		$group->display .=  $html;

		return true;
	}
	
	/*
	 * Get characteristic id in all characteristic values hash table
	 * or hash new one and get it's id
	 * @ $colname name of characeristic
	 * @ $conent value of characteristic
	 */
	private function isFieldContentExists($colName,$content)
	{
		$db=&JFactory::getDBO();
		$query='SELECT id FROM #__virtuemart_product_custom_plg_carriage_content as pcc ' .
			   'WHERE col_name="'.$colName.'" AND content="'.$content.'"';
		$db->setQuery($query);
		$db->query();
		if($db->getNumRows()==0)
		{
			$query='INSERT INTO #__virtuemart_product_custom_plg_carriage_content (`col_name`,`content`) ' .
						   'VALUES ("'.$colName.'","'.$content.'");';
			$db->setQuery($query);
			$db->query();
			return $db->insertid();
		}	
		return $db->loadResult();
	}

	/*
	 * Store plugin additional data of product in DB
	 * @ $data all info about product
	 * @ $plugin_param info about product to store in plugin
	 */	
	function plgVmOnStoreProduct(&$data,$plugin_param){
                        if (key ($plugin_param) !== $this->_name) {
                
			return;
		}
                
		//var_dump($data);die();
		$db=&JFactory::getDBO();
		/*if($data['virtuemart_manufacturer_id']!=0)
		{
			$db->setQuery('SELECT mf_name FROM #__virtuemart_manufacturers_ru_ru ' .
						  'WHERE virtuemart_manufacturer_id='.$data['virtuemart_manufacturer_id']);
			$plugin_param[$this->_name]['brend']=$db->loadResult();
		}*/
		foreach($this->_varsToPushParam as $k=>$v)
		{
			$contID=$this->isFieldContentExists($k,$plugin_param[$this->_name][$k]);
			foreach($data['categories'] as $category){
				$db->setQuery('DELETE FROM #__virtuemart_product_custom_plg_carriage_cats ' .
							  'WHERE content_id='.$contID.
							  ' AND virtuemart_category_id='.$category);
				if(!$db->query()) die($db->getQuery());
				$db->setQuery('INSERT INTO #__virtuemart_product_custom_plg_carriage_cats '.
								'(`content_id`,`virtuemart_category_id`) ' .
								'VALUES ('.$contID.','.$category.')');
				if(!$db->query()) die($db->getQuery());
			}
			$plugin_param[$this->_name][$k]=$contID;
		}
		$res=$this->OnStoreProduct($data,$plugin_param);
		foreach($this->_varsToPushParam as $k=>$v)
		{
			$db->setQuery('DELETE pcnt.* FROM #__virtuemart_product_custom_plg_carriage_cats AS pcc ' .
							  'LEFT JOIN #__virtuemart_product_custom_plg_carriage_content AS pcnt ON ' .
							  'pcc.content_id = pcnt.id ' .
							  'LEFT JOIN  #__virtuemart_product_custom_plg_carriagechars AS pc ' .
							  'ON pcnt.id = pc.'.$k.
							  ' WHERE pcnt.col_name="'.$k.'" AND pc.id IS NULL');
			$db->query();			
		}
		return $res;
	}
	/**
	 * We must reimplement this triggers for joomla 1.7
	 * vmplugin triggers note by Max Milbers
	 */
	public function plgVmOnStoreInstallPluginTable($psType,$name) {
		return $this->onStoreInstallPluginTable($psType,$name);
	}

	function plgVmSetOnTablePluginParamsCustom($name, $id, &$table){
		return $this->setOnTablePluginParams($name, $id, $table);
	}

	function plgVmDeclarePluginParams($psType,$name,$id, &$data){
		return $this->declarePluginParams('custom', $name, $id, $data);
	}

	/**
	 * Custom triggers note by Max Milbers
	 */
	function plgVmOnDisplayEdit($virtuemart_custom_id,&$customPlugin){
		return $this->onDisplayEditBECustom($virtuemart_custom_id,$customPlugin);
	}

}

// No closing tag