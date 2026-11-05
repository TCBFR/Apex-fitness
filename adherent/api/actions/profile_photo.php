<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiReply(['error'=>'Méthode non autorisée.'], 405);
            $photoPath = $adherentController->changerPhotoProfil($id, $_FILES['photo'] ?? []);
            journalise('MODIFICATION_PHOTO_PROFIL');
            apiReply(['photo_url'=>url($photoPath)]);
