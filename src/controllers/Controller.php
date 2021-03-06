<?hh // strict

abstract class Controller {
  abstract protected function getTitle(): string;
  abstract protected function getFilters(): array<string, mixed>;
  abstract protected function getPages(): array<string>;

  abstract protected function genRenderBody(string $page): Awaitable<:xhp>;

  public async function genRender(): Awaitable<:xhp> {
    $page = $this->processRequest();
    $body = await $this->genRenderBody($page);
	$config = await Configuration::gen('language');
    $language = $config->getValue();
	$document_root = must_have_string(Utils::getSERVER(), 'DOCUMENT_ROOT');
	$localize_style="";
	if (! preg_match('/^\w{2}$/', $language)) {
		$language = "en";
	}
	if (file_exists($document_root."/static/css/locals/".$language."/style.css")){
		$localize_style = <link rel="stylesheet" href="static/css/locals/".$language."/style.css" />
	}
    return
      <x:doctype>
        <html lang={$language}>
          <head>
            <meta http-equiv="Cache-control" content="no-cache" />
            <meta http-equiv="Expires" content="-1" />
            <meta charset="UTF-8" />
            <meta
              name="viewport"
              content="width=device-width, initial-scale=1"
            />
            <title>{$this->getTitle()}</title>
            <link
              rel="icon"
              type="image/png"
              href="static/img/favicon.png"
            />
            <link rel="stylesheet" href="static/css/fb-ctf.css" />
			{$localize_style}
          </head>
          {$body}
        </html>
      </x:doctype>;
  }

  private function processRequest(): string {
    $input_methods = array('POST' => INPUT_POST, 'GET' => INPUT_GET);
    $method = must_have_string(Utils::getSERVER(), 'REQUEST_METHOD');

    $filter = idx($this->getFilters(), $method);
    if ($filter === null) {
      // Method not supported
      return 'none';
    }

    $input_method = must_have_idx($input_methods, $method);
    $page = 'main';

    $parameters = filter_input_array($input_method, $filter);

    $page = idx($parameters, 'page', 'main');
    if (!in_array($page, $this->getPages())) {
      $page = 'main';
    }

    return $page;
  }
}
