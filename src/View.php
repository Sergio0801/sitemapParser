<?php

namespace app\src;

/**
 * View class
 *
 */
class View
{
    private string $css;
    private ?array $urls = [];
    private string $rows;

    public function __construct(array $getURLs)
    {
        if (!empty($getURLs)) {
            $this->urls = $getURLs;
        }
    }

    /**
     * @return string
     */
    public function renderHTML()
    {
        $this->setCSS();
        $this->getRows();

        return <<<HTML
<html>
<head>
<style>{$this->css}</style>
</head>
<body>
<div class="table">
{$this->rows}
</div>
</body>
</html>
HTML;
    }

    /**
     * @return string
     */
    protected function getRows(): void
    {
        $this->rows = '';
        foreach ($this->urls as $url => $tags) {
            $this->rows .= '<div class="row">';
            $this->rows .= '<div class="cell">' . $url . '</div>';

            $this->rows .= '<div class="cell">' . ($tags['h1'] ?? '') . '</div>';
            $this->rows .= '<div class="cell">' . ($tags['h2'] ?? '') . '</div>';

            $this->rows .= '</div>';
        }
    }

    /**
     * ADD CSS to html
     * @return string
     */
    protected function setCSS()
    {
       $this->css = <<<CSS
    .table {
            display:table;
            width: 100%;
            border-collapse: collapse;
        }
        .row {
            display:table-row;
        }
        .cell {
            display: table-cell;
            text-align: left;
            border: 1px solid #000;
            vertical-align: middle;
        }
CSS;
    }
}