<?php

if ($_SERVER['REQUEST_METHOD'] === 'GET') apiReply($space->profil($id));
            $space->updateProfile($id, $input);
            apiReply(['profile'=>$space->profil($id)]);
