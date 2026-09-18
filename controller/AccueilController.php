<?php

final class AccueilController
{
    public function getAll(): array
    {
        return db()->query(
            'SELECT * FROM activite ORDER BY libelleact'
        )->fetchAll();
    }
}
