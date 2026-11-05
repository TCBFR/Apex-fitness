<?php

$start = (string)($_GET['start'] ?? '');
            $end = (string)($_GET['end'] ?? '');
            if (!strtotime($start) || !strtotime($end)) apiReply(['error'=>'Période invalide.'], 400);
            $events = $planning->evenements(date('Y-m-d H:i:s', strtotime($start)), date('Y-m-d H:i:s', strtotime($end)), $id);
            foreach ($events as &$event) {
                $p = &$event['extendedProps'];
                $p['category'] = $p['type'];
                $p['coach_full'] = $p['coach'];
                $p['room'] = $p['salle'];
                $p['level'] = $p['typeLibelle'];
                $p['capacity'] = $p['capacite'];
                $p['booked'] = $p['prises'];
                $p['remaining'] = $p['restantes'];
                $p['is_booked'] = $p['reserve'];
                $p['is_waitlisted'] = $p['enAttente'];
                $p['can_cancel'] = strtotime($event['start']) - time() >= 2 * 3600;
                $p['is_past'] = $p['passe'];
                $p['color'] = CATEGORIES[$p['category']]['color'] ?? '#4F7DFF';
                $p['image'] = CAT_IMAGES[$p['category']] ?? '';
                $p['id'] = (string)$event['id'];
                $p['title'] = $event['title'];
                $p['start'] = $event['start'];
                $p['end'] = $event['end'];
                $p['date'] = substr($event['start'], 0, 10);
                unset($p);
            }
            apiReply($events);
