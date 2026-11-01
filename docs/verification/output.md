# Vérification de la sortie JSON-LD (2026-11-01)

Banc : WordPress 7.1.4 vierge, Twenty Twenty-Five, Foldmark synchronisé depuis le paquet.

Page de démo : deux accordéons FAQ (2 + 2 items), une question en double, une réponse en liste.

| Contrôle | Résultat |
|---|---|
| Scripts JSON-LD dans la page | 1 |
| Nœuds | 1 `FAQPage`, 3 questions (doublon écarté), ordre du document |
| Validateur schema.org (`validator.schema.org/validate`) | 1 objet, 0 erreur, 0 avertissement |
| Rich Results Test Google | « Aucun élément détecté », y compris pour un `FAQPage` minimal à la racine |

Le Rich Results Test ne reconnaît plus le type FAQ : Google a retiré les résultats
enrichis FAQ le 7 mai 2026 et leur prise en charge dans l'outil en juin 2026
(https://developers.google.com/search/updates). Le balisage reste un type
schema.org valide, lu par Google pour comprendre la page et par les moteurs d'IA.
