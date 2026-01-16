<?php

echo '<style>
body::before {
    content: "!!! PRODUCTION DATABASE !!!";
    display: block;
    background: #c00;
    color: #fff;
    font-size: 2em;
    text-align: center;
    padding: 15px 0;
    z-index: 9999;
}
</style>';
