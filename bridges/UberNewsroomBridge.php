<?php
class UberNewsroomBridge extends BridgeAbstract {
	const NAME = 'Uber Newsroom Bridge';
	const URI = 'https://www.uber.com';
	const URI_API_DATA = 'https://newsroomapi.uber.com/wp-json/newsroom/v1/data?locale=';
	const URI_API_POST = 'https://newsroomapi.uber.com/wp-json/wp/v2/posts/';
	const DESCRIPTION = 'Returns news posts';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
		'region' => array(
			'name' => 'Region',
			'type' => 'list',
			'values' => array(
				'Africa' => array(
					'Egypt' => 'en-EG',
					'Ghana' => 'en-GH',
					'Kenya' => 'en-KE',
					'Morocco' => 'en-MA',
					'Nigeria' => 'en-NG',
					'South Africa' => 'en-ZA',
					'Tanzania' => 'en-TZ',
					'Uganda' => 'en-UG',
				),
				'Asia' => array(
					'Bangladesh' => 'en-BD',
					'Cambodia' => 'en-KH',
					'China' => 'en-CN',
					'Hong Kong' => 'en-HK',
					'India' => 'en-IN',
					'Indonesia' => 'en-ID',
					'Japan' => 'ja-JP',
					'Korea' => 'en-KR',
					'Macau' => 'en-MO',
					'Malaysia' => 'en-MY',
					'Myanmar' => 'en-MM',
					'Philippines' => 'en-PH',
					'Singapore' => 'en-SG',
					'Sri Lanka' => 'en-LK',
					'Taiwan' => 'en-TW',
					'Thailand' => 'en-TH',
					'Vietnam' => 'en-VN',
				),
				'Central America' => array(
					'Costa Rica' => 'es-CR',
					'Dominican Republic' => 'es-DO',
					'El Salvador' => 'es-SV',
					'Guatemala' => 'es-GT',
					'Honduras' => 'en-HN',
					'Mexico' => 'es-MX',
					'Nicaragua' => 'es-NI',
					'Panama' => 'es-PA',
					'Puerto Rico' => 'en-PR',
				),
				'Europe' => array(
					'Austria' => 'de-AT',
					'Azerbaijan' => 'az',
					'Belarus' => 'ru-BY',
					'Belgium' => 'en-BE',
					'Bulgaria' => 'en-BG',
					'Croatia' => 'hr',
					'Czech Republic' => 'cs-CZ',
					'Denmark' => 'en-DK',
					'Estonia' => 'en-EE',
					'Finland' => 'en-FI',
					'France' => 'en-FR',
					'Germany' => 'en-DE',
					'Greece' => 'en-GR',
					'Hungary' => 'en-HU',
					'Ireland' => 'en-IE',
					'Italy' => 'en-IT',
					'Kazakhstan' => 'ru-KZ',
					'Lithuania' => 'en-LT',
					'Netherlands' => 'en-NL',
					'Norway' => 'en-NO',
					'Poland' => 'pl',
					'Portugal' => 'en-PT',
					'Romania' => 'en-RO',
					'Russia' => 'ru',
					'Slovakia' => 'sk',
					'Spain' => 'es-ES',
					'Sweden' => 'en-SE',
					'Switzerland' => 'en-CH',
					'Turkey' => 'en-TR',
					'Ukraine' => 'uk-UA',
					'United Kingdom' => 'en-GB',
				),
				'Middle East' => array(
					'Bahrain' => 'en-BH',
					'Israel' => 'en-IL',
					'Jordan' => 'en-JO',
					'Kuwait' => 'en-KW',
					'Lebanon' => 'en-LB',
					'Pakistan' => 'en-PK',
					'Qatar' => 'en-QA',
					'Saudi Arabia' => 'en-SA',
					'United Arab Emirates' => 'en-AE',
				),
				'North America' => array(
					'Canada' => 'en-CA',
					'United States' => 'en-US',
				),
				'Pacific' => array(
					'Australia' => 'en-AU',
					'New Zealand' => 'en-NZ',
				),
				'South America' => array(
					'Argentina' => 'es-AR',
					'Bolivia' => 'es-BO',
					'Brazil' => 'pt-BR',
					'Chile' => 'es-CL',
					'Colombia' => 'es-CO',
					'Ecuador' => 'es-EC',
					'Paraguay' => 'en-PY',
					'Peru' => 'es-PE',
					'Trinidad & Tobago' => 'en-TT',
					'Uruguay' => 'es-UY',
					'Venezuela' => 'en-VE',
				),
			),
			'defaultValue' => 'en-US',
		)
	));

	const CACHE_TIMEOUT = 3600;

	private $regionName = '';

	public function collectData() {
		$json = getContents(self::URI_API_DATA . $this->getInput('region'));
		$data = json_decode($json);

		$this->regionName = $data->region->name;

		foreach ($data->articles as $article) {
			$json = getContents(self::URI_API_POST . $article->id);
			$post = json_decode($json);

			$item = array();
			$item['title'] = $post->title->rendered;
			$item['timestamp'] = $post->date;
			$item['uri'] = $post->link;
			$item['content'] = $this->formatContent($post->content->rendered);
			$item['enclosures'][] = $article->image_full;

			$this->items[] = $item;
		}
	}

	public function getURI() {
		if (is_null($this->getInput('region')) === false) {
			return self::URI . '/' . $this->getInput('region') . '/newsroom';
		}

		return parent::getURI() . '/newsroom';
	}

	public function getName() {
		if (is_null($this->getInput('region')) === false) {
			return $this->regionName . ' - Uber Newsroom';
		}

		return parent::getName();
	}

	private function formatContent($html) {
		$html = str_get_html($html);

		foreach ($html->find('div.wp-video') as $div) {
			$div->style = '';
		}

		foreach ($html->find('video') as $video) {
			$video->width = '100%';
			$video->height = '';
		}

		return $html;
	}
}
