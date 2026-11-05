# Comprendre `self::` en PHP – version débutant

`self::` c'est juste le moyen en PHP de dire **« utilise la méthode (ou la propriété) qui appartient à la classe où j'écris ce code »**.

| Terme | Image mentale | Que fait‑il ? |
|-------|---------------|---------------|
| **`self::`** | Tu pointes du doigt ta propre boîte à outils. | On appelle une méthode statique ou on accède à une propriété qui appartient à la **même classe** où tu te trouves. |
| **`static::`** | Tu pointes du doigt « celle qui sera déterminée à l’exécution ». | S'utilise surtout quand tu veux que la méthode soit remplacée dans une classe fille. |
| **`Parent::`** | Tu points du doigt la boîte de ton père (la classe parente). | On appelle une méthode définie dans la classe parent. |

### Exemple concret

```php
class MaClasse {
    public const VERSION = '1.0';

    public static function prix(float $montant): string {
        return '$' . number_format($montant, 2, ',', ' ');
    }
}

// Utilisation depuis à l'intérieur de la classe :
echo self::prix(49.50);   // Affiche : $49,50
```

### alternatives plus "débutant"

1. **Function globale** – Pas besoin de `self::` :
   ```php
   function prix(float $montant): string {
       return '$' . number_format($montant, 2, ',', ' ');
   }
   echo prix(49.50);
   ```

2. **Méthode d'instance** – Si tu as un objet, utilise `$this->`.

3. **Renommer la méthode** – Mettre un nom explicite comme `obtenirPrix()`.

4. **Utiliser le nom de la classe** – `MaClasse::prix(49.50)` (plus verbeux mais clair).

---

Dans ce projet, beaucoup de fichiers utilisent `self::` pour appeler des méthodes statiques comme `prix()`, `VERSION`, `reglementSalle()`, etc. Si tu préfères éviter `self::`, tu peux convertir ces appels en fonctions globales (voir le fichier `config/function.php` pour des exemples de helpers).