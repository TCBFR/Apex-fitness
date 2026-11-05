<?php

$items = $space->reservations($id);
            if (isset($items[0])) apiReply(['session'=>apiSession($items[0])]);
            apiReply(['session'=>null]);
