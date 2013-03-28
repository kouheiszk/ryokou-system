<?php

App::uses('PlanModel', 'Model');

class Cplan extends PlanModel
{
	public $useDbConfig = 'yado';
	public $useTable = 'cpln';
	
	public $plan_types = array(
		'pink' => 'ピンク',
		'soft' => 'ソフト',
		'cos' => 'コスチューム',
		'normal' => 'ノーマル'
	);
	
	public $validate = array(
		'hotel_id' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => 'hotel_idは必須です。'
	        )
        ),
        'type' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => 'プランの種類を選択してください。'
	        )
        ),
        'title' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => 'プランのタイトルを入力してください。',
	        )
        ),
        'content' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => 'プラン内容を入力してください。',
	        )
        ),
        'profile' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => 'プランの説明を入力してください。',
	        )
        )
    );
	
	public $path = array(
		'path' => '/home/ryoko487/www/ryokou-ya.co.jp/plan/companion/', 
		'url'  => 'http://ryokou-ya.co.jp/plan/companion/'
	);

	public function beforeSave($options = array())
	{
		// 最安値料金設定
		$min_price = 0;

		if($min_price)
		{
			$this->data['Cplan']['min_price'] = $min_price;
		}
		
		// プラン画像3枚
		// roman
		if(isset($this->data['Hotel']['roman'])) $roman = $this->data['Hotel']['roman'];
		else $roman = $this->Hotel->field('roman', array('Hotel.id' => $this->data[$this->alias]['hotel_id']));
		
		// path
		if(! isset($this->HotelImage))
		{
			App::uses('HotelImage', 'Model');
			$this->HotelImage = new HotelImage();
		}
		$dir = $this->HotelImage->path['path'];
		
		for($i = 1; $i <= 3; $i++)
		{
			if(isset($this->data[$this->alias]['pic_'.$i]) && is_array($this->data[$this->alias]['pic_'.$i]))
			{
				$tmp = $this->data[$this->alias]['pic_'.$i];
				$this->data[$this->alias]['pic_'.$i] = null;
				
				// 保存
				if($this->data[$this->alias]['id'])
				{
					$file = 'c_plan_'.$this->data[$this->alias]['id'].'_pic_'.$i.'.jpg';
					$src = $tmp['tmp_name'];
					$dest = $dir.$roman.DS.$file;
				
					if(file_exists($src))
					{
						move_uploaded_file($src, $dest);
						$this->data[$this->alias]['pic_'.$i] = $file;
					}
				}
			}
		}
			
		return parent::beforeSave($options);
	}
}
