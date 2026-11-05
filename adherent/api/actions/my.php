<?php

apiReply(array_map('apiSession', $space->reservations($id)));
