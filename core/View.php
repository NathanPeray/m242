<?php
    class View {
        function __construct($viewName, $title = "", $content = []) {
            global $confArray;
            $viewName = explode(".", $viewName);
            $this->currentDir = $viewName[0];
            foreach ($content as $key => $value) {
                ${$key} = $value;
            }
            $auth = Auth::getInstance();
            $viewContent = file_get_contents("./../view/" . $viewName[0] . "/" . $viewName[1] . ".view.php");
            $layout = file_get_contents("./../view/layout.view.php");
            $viewContent = str_replace("@@content", $viewContent, $layout);
            $partials = $this->findPartials($viewContent);
            foreach ($partials as $key => $value) {
                $viewContent = str_replace($key, $value, $viewContent);
            }
            while (substr_count($viewContent, "@@partial(") > 0) {
                $partials = $this->findPartials($viewContent);
                foreach ($partials as $key => $value) {
                    $viewContent = str_replace($key, $value, $viewContent);
                }
            }
            $viewContent = str_replace("@@auth", "<?php if (Auth::getInstance()->auth()): ?>", $viewContent);
            $viewContent = str_replace("@@else", "<?php else: ?>", $viewContent);
            $viewContent = str_replace("@@endauth", "<?php endif; ?>", $viewContent);
            $viewContent = str_replace("@@title", $title, $viewContent);
            //$viewContent = str_replace("@@scripts", "var scripts = " . json_encode($confArray['scripts']) . ";", $viewContent);
            $viewContent = str_replace("@@baseUrl", $confArray['protocol']  . "://" . $confArray['base_url'], $viewContent);
            $viewContent;
            echo eval("?>" . $viewContent);
        }
        function findPartials($viewContent) {
            $identifier = "@@partial(";
            $partials = [];
            $count = substr_count($viewContent, $identifier);
            $done = 0;
            while ($done < $count) {
                $index = strpos($viewContent, $identifier);
                $content = substr($viewContent, $index);
                $key = substr($content, 0, strpos($content, ")") + 1);
                $partials[$key] = $this->getPartial($key);
                $amount = 1;
                $viewContent = str_replace($key, "", $viewContent, $amount);
                $done++;
            }
            return $partials;
        }
        function getPartial($key) {
            $key = explode("(", $key);
            $view = str_replace(")", "", $key[1]);
            $view = explode(".", $view);
            if (sizeof($view) > 1) {
                $dir = $view[0];
                $view = $view[1];
            } else {
                $dir = $this->currentDir;
                $view = $view[0];
            }
            return file_get_contents("./../view/$dir/$view.view.php");
        }
    }
?>
