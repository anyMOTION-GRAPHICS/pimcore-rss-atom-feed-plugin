<?php

/**
 * This controller uses Zend_Feed_Writer to generate ATOM and RSS feeds with the published documents of a Pimcore website.
*/
class Feed_FeedController extends Website\Controller\Action {

	protected $defaultTitle;
	protected $baseUrl;
	protected $documentBody;
	protected $limit = null;
	protected $offset = null;
	protected $path = '';
	protected $author = '';
	protected $description = '';

	public function init() {
		parent::init();

		$this->path = $this->getParam('FeedPath');
		$this->limit = $this->getParam('FeedLimit');
		$this->offset = $this->getParam('FeedOffset');

		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$this->baseUrl = $protocol . $_SERVER['HTTP_HOST'];

		$this->author = $this->_getPropertyOrConfigValueByName('FeedAuthor');
		$this->description = $this->_getPropertyOrConfigValueByName('FeedDescription');
		$this->defaultTitle = $this->_getPropertyOrConfigValueByName('FeedDefaultTitle');
	}

	/**
	 * Get most recently created documents (with type "page")
	 * @param int $limit Maximum number of documents to return. Default is 25.
	 * @return object Document_List
	*/
	protected function getPages($path, $limit = null, $offset = null) {
		$list = new \Pimcore\Model\Document\Listing();

		if ($path != null) {
			$list->setCondition("type = 'page' AND path LIKE '". $path ."%'");
		} else {
			$list->setCondition("type = 'page'");
		}

		if ($limit != null) {
			$list->setLimit($limit);
		}
		if ($offset != null) {
			$list->setOffset($offset);
		}

		return $list->load();
	}

	/**
	 * Create an Atom feed.
	*/
	public function atomAction() {
		$documents = $this->getPages($this->path, $this->limit, $this->offset);

		$feed = new Zend_Feed_Writer_Feed;
		$feed->setTitle($this->defaultTitle);
		$feed->setLink($this->baseUrl.'/');
		$feed->setFeedLink($this->baseUrl . $_SERVER['REQUEST_URI'], 'atom');
		$feed->setId($this->baseUrl);
		$feed->addAuthor(array('name' => $this->author));

		$modDate = 0;

		foreach($documents as $document) {

			if($document->hasProperty('showInFeed') && !$document->getProperty('showInFeed')) {
				continue;
			}

			if($document->getModificationDate() > $modDate) {
				$modDate = $document->getModificationDate();
			}

			$content = trim(str_replace('&nbsp;', ' ', $document->getDescription()));
			$descr = $document->getDescription();
			$title = $document->title;
			if(empty($title)) {
				$title = $this->defaultTitle;
			}

			$entry = $feed->createEntry();
			$entry->setTitle($title);
			$entry->setLink($this->baseUrl.$document->getFullPath());
			$entry->setDateModified($document->getModificationDate());
			$entry->setDateCreated($document->getCreationDate());
			if(!empty($descr)) {
				$entry->setDescription($descr);
			}
			if(!empty($content)) {
				$entry->setContent($content);
			}
			$feed->addEntry($entry);
		}

		$feed->setDateModified($modDate);

		$this->getResponse()->setHeader('Content-Type', 'application/atom+xml; charset=utf-8');
		echo $feed->export('atom');
		exit();
	}

	/**
	 * Create an RSS feed.
	*/
	public function rssAction() {
		$documents = $this->getPages($this->path, $this->limit, $this->offset);

		$description = $this->description;

		$feed = new Zend_Feed_Writer_Feed;
		$feed->setTitle($this->defaultTitle);
		if(empty($this->description)) {
			$description = $this->defaultTitle;
		}
		$feed->setDescription($description);
		$feed->setLink($this->baseUrl.'/');
		$feed->setFeedLink($this->baseUrl.$_SERVER['REQUEST_URI'], 'rss');
		$feed->setId($this->baseUrl);
		$feed->addAuthor($this->author);

		$modDate = 0;

		foreach($documents as $document) {

			if($document->hasProperty('showInFeed') && !$document->getProperty('showInFeed')) {
				continue;
			}

			if($document->getModificationDate() > $modDate) {
				$modDate = $document->getModificationDate();
			}

			$content = trim(str_replace('&nbsp;', ' ', $document->elements[$this->documentBody]->text));
			$descr = $document->getDescription();
			$title = $document->title;
			if(empty($title)) {
				$title = $this->defaultTitle;
			}

			$entry = $feed->createEntry();
			$entry->setTitle($title);
			$entry->setLink($this->baseUrl.$document->getFullPath());
			$entry->setDateModified($document->getModificationDate());
			$entry->setDateCreated($document->getCreationDate());
			if(!empty($descr)) {
				$entry->setDescription($descr);
			}
			if(!empty($content)) {
				$entry->setContent($content);
			}
			$feed->addEntry($entry);
		}

		$feed->setDateModified($modDate);

		$this->getResponse()->setHeader('Content-Type', 'application/rss+xml; charset=utf-8');
		echo $feed->export('rss');
		exit();
	}

	private function _getPropertyOrConfigValueByName($configName, $throwException = true) {

		if($this->config->get($configName) != null || $this->document->getProperty($configName) != null) {
			if ($this->document->getProperty($configName) != null) {
				return $this->document->getProperty($configName);
			}

			return $this->config->get($configName);

		} else {
			if ($throwException) {
				throw new Exception('No website setting or document property with key "'. $configName .'" found.');
			}
			return null;
		}

	}

}
