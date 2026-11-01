# Foldmark : conception

Date : 2026-11-01
Statut : en attente de validation
Dépôt : https://github.com/Fyrins/foldmark

## Intention

Foldmark est un plugin destiné au dépôt officiel wordpress.org. Il permet de
publier un bloc `core/accordion` comme FAQ : le plugin produit un balisage
schema.org `FAQPage` en JSON-LD construit à partir de la structure de
l'accordéon. La version 1 couvre la FAQ ; l'architecture doit permettre d'ajouter
d'autres types plus tard sans changer la façon dont les rédacteurs marquent leurs
blocs.

Il suit la philosophie de Tablefold (https://github.com/Fyrins/tablefold), même
auteur, même famille : il étend un bloc du cœur, travaille côté serveur, n'écrit
rien dans le contenu sauvegardé, ne modifie pas le DOM du bloc et n'a pas de page
de réglages.

## Pourquoi

Les accordéons servent de FAQ sur beaucoup de sites, mais aucune donnée
structurée ne les accompagne. Deux objectifs, d'importance égale :

1. Donner une structure Question/Réponse explicite aux moteurs d'IA, à Bing et
   aux assistants.
2. Rendre les pages éligibles aux résultats enrichis Google.

Contrainte connue et assumée : depuis août 2023, Google n'affiche les résultats
enrichis FAQ que pour des sites gouvernementaux et de santé qui font autorité. Le
plugin produit un balisage valide et éligible ; il ne promet aucun affichage. Le
readme le dit en ces termes (guideline 9 : aucune revendication de résultat).

**Mise à jour du 2026-11-01, constatée pendant l'implémentation :** Google a retiré
les résultats enrichis FAQ pour tous les sites le 7 mai 2026, puis leur prise en
charge dans le Rich Results Test et la Search Console en juin 2026
(https://developers.google.com/search/updates). L'objectif 2 n'existe donc plus.
`FAQPage` reste un type schema.org valide que Google dit continuer à lire pour
comprendre les pages, et que les moteurs d'IA exploitent : l'objectif 1 tient. Le
readme et le README le disent explicitement.

## Pourquoi `core/accordion` et pas `core/details`

Vérifié sur le trunk de Gutenberg le 2026-11-01.

- `core/details` n'a pas de conteneur commun : une FAQ y est faite de blocs
  indépendants, et les regrouper oblige à collecter dans toute la page. Son
  `summary` n'est pas un titre.
- `core/accordion` (WP 6.9) a exactement la structure d'une FAQ : conteneur
  `.wp-block-accordion` → items `.wp-block-accordion-item` → titre
  `.wp-block-accordion-heading` contenant
  `.wp-block-accordion-heading__toggle-title` (la question) → panneau
  `.wp-block-accordion-panel` dont les blocs enfants forment la réponse. Les
  questions sont de vrais titres `hN`.

La microdata a été écartée : `Answer.text` exige un élément distinct du panneau,
or les blocs de réponse sont enfants directs du panneau (layout flow). Il faudrait
injecter un élément, ce qui modifie le DOM et le comportement `:first-child` /
`:last-child` du layout.

## Objectifs

- Une variation de bloc **FAQ** de `core/accordion`, pré-paramétrée : visible
  dans l'inserteur, insérée avec des items prêts à remplir, et accessible depuis
  le sélecteur de variations pour convertir un accordéon existant (et revenir en
  arrière).
- Chaque item d'un accordéon coché devient une `Question` : le titre donne
  `name`, le panneau donne `acceptedAnswer.text`.
- Un seul `<script type="application/ld+json">` par page.
- Des filtres pour couper la sortie et pour récupérer les données, afin que les
  développeurs puissent les injecter dans le graphe de leur plugin SEO.
- Publication sur wordpress.org.

## Non-objectifs (v1)

- Prise en charge de `core/details`.
- Intégration native au `@graph` de Yoast, Rank Math ou SEOPress.
- D'autres types que `FAQPage` (l'attribut est prévu pour, l'interface non).
- Toute modification de l'apparence, du comportement ou du HTML de l'accordéon.
- Page de réglages, option en base, appel réseau, télémétrie.

## Anti-objectifs (échec même si « ça marche »)

- Désactiver le plugin invalide des blocs existants dans l'éditeur.
- Le JSON-LD contient une question ou une réponse absente du contenu visible.
- Le readme, les visuels ou le nom laissent entendre un résultat garanti dans
  Google.
- Le plugin publie un `FAQPage` qui agrège les FAQ de plusieurs articles
  (archives, Query Loop d'articles liés).
- Deux `FAQPage` sur la même page.

## Décisions prises par Alexandre

| Sujet | Décision |
|---|---|
| Nom | **Foldmark** ; slug, text domain et préfixes : `foldmark` / `foldmark_` / `Foldmark_` / `FOLDMARK_` |
| Bloc cible | `core/accordion` (WP 6.9 minimum) |
| Format | JSON-LD construit depuis la structure de l'accordéon ; pas de microdata |
| Ergonomie | Variation de bloc « FAQ » pré-paramétrée, sans toggle |
| Langue | Code et chaînes en anglais, traduction française livrée dès la 1.0 |
| Périmètre | FAQ en v1, architecture extensible à d'autres types |
| Source des questions | Le contenu principal de l'objet affiché uniquement, analysé avant `wp_head` |
| Plugins SEO | Script autonome + filtres ; pas d'intégration native |
| Objectifs | Compréhension IA/moteurs **et** éligibilité rich results |

## Choix de Claude (à relire)

- **Un accordéon = une FAQ.** La variation s'applique au conteneur, pas aux
  items.
- **Pré-paramétrage** : trois items vides (titre + panneau avec un paragraphe),
  niveau de titre 3 hérité de l'accordéon.
- **Conventions de `bsaweb-blocks`** : variation nommée `foldmark/faq`, attribut
  objet namespacé `foldmark: { type: "faq" }`, `isActive: ["foldmark.type"]`,
  `scope: ["inserter", "transform"]`. Une seconde variation `foldmark/accordion`
  (scope `transform` seul) remet `foldmark` à `null` pour revenir à un
  accordéon simple.
- **Plusieurs accordéons cochés sur une page** : leurs items sont fusionnés dans
  un seul `FAQPage`.
- **Enveloppe `@graph` dès la v1** : le script contient
  `{"@context":"https://schema.org","@graph":[ … ]}`. Les futurs types s'ajoutent
  au même graphe sans casser l'API.
- **Conflit avec un FAQPage existant** : si le contenu contient un bloc FAQ
  connu (`yoast/faq-block`, `rank-math/faq-block`), Foldmark ne sort rien. Liste
  filtrable. Simple vérification de nom de bloc, pas une intégration.
- **Réponse construite depuis le markup sauvegardé** des blocs du panneau. Les
  blocs dynamiques (sans markup sauvegardé) n'apparaissent pas dans la réponse :
  limite acceptée et documentée, qui évite d'exécuter le rendu deux fois.

## Contraintes techniques

- **Rien dans le markup sauvegardé.** L'attribut ajouté à `core/accordion` n'a pas
  de `source` : il vit dans le commentaire de bloc, que la validation ignore. Pas
  de `blocks.getSaveContent.extraProps`, pas de `render_block` qui modifie le HTML.
- **Analyse en amont.** Sur une vue singulière (`is_singular()`), l'objet
  principal (`get_queried_object()`) est analysé avec `parse_blocks()` avant
  `wp_head` ; le JSON-LD est imprimé dans `wp_head`. Les patterns synchronisés
  (`core/block`) sont résolus récursivement, avec une garde contre les boucles.
  Rien sur archives, flux, REST, éditeur, ni sur une page protégée par mot de
  passe tant que le mot de passe n'est pas saisi.
- **Extraction.** Question : texte brut du titre de l'item (balises retirées,
  entités décodées). Réponse : HTML des blocs du panneau, nettoyé par `wp_kses`
  avec la liste acceptée par Google pour `Answer.text` : `h1`–`h6`, `br`, `ol`,
  `ul`, `li`, `a`, `p`, `div`, `b`, `strong`, `i`, `em`. Le HTML est lu avec la
  HTML API du cœur, pas avec des expressions régulières.
- **Robustesse.** Item sans question ou sans réponse : ignoré. Questions en double
  (texte normalisé) : émises une fois. Aucun `FAQPage` sans au moins une question
  valide. Accordéon imbriqué dans un panneau : ignoré pour le schéma, son texte
  reste dans la réponse parente.
- **Attribut extensible.** Attribut objet `foldmark` (`type: "object"`), dont la
  clé `type` porte le type de schéma (`"faq"` en v1). Un futur type = une
  nouvelle variation et une nouvelle valeur. Un type inconnu, un `null` ou une
  valeur non objet sont ignorés sans erreur.
- **Entrées non fiables.** Vérifier `is_scalar()` avant tout cast d'attribut.
- **Sortie.** `wp_json_encode` avec au minimum `JSON_HEX_TAG`, pour qu'aucune
  chaîne ne ferme le `<script>`.
- **Éditeur : 100 % PHP, aucun JavaScript.** Attribut ajouté via le filtre
  `register_block_type_args`, variations via `get_block_type_variations`
  (WP 6.5+). Validé par le spike (section suivante). Donc pas de
  `@wordpress/scripts`, pas de build, aucun asset, ni éditeur ni front.
- **i18n.** Text domain `foldmark`, chaînes source en anglais. Traduction
  française (`languages/foldmark-fr_FR.po`/`.mo` + `.pot`) livrée dans la 1.0.
  **À prouver dans le plan** : que le `.mo` embarqué est chargé par le chargement
  juste-à-temps sans `load_plugin_textdomain()` ; à défaut, faire publier le pack
  `fr_FR` sur translate.wordpress.org pour le lancement. Les titres et
  descriptions des variations sont traduits côté PHP (`__()` appelé après
  `init`, dans le filtre).
- **Versions.** WordPress 6.9 (arrivée de `core/accordion`), PHP 7.4.
- **Qualité.** PHPCS (ruleset WordPress, préfixe, text domain), Plugin Check sans
  erreur ni avertissement sur le paquet construit, tests PHPUnit sur l'extraction
  et l'agrégation, vérification sur un WordPress vierge avec `WP_DEBUG`, contrôle
  du JSON-LD dans le Rich Results Test et le validateur schema.org.
- **Packaging.** `.distignore` + `wp dist-archive`, comme Tablefold.

## Spike du 2026-11-01 (jetable, WordPress 7.1.4 vierge, Twenty Twenty-Five)

Plugin de 60 lignes en PHP pur, aucun JS. Résultats :

| Contrôle | Résultat |
|---|---|
| L'attribut ajouté côté serveur arrive dans l'éditeur | ✅ `getBlockType('core/accordion').attributes.foldmark` présent |
| Les variations PHP arrivent dans l'éditeur, `innerBlocks` compris | ✅ `foldmark/faq` avec 3 items, `isActive: ["foldmark.type"]` reconnu |
| L'attribut est sauvegardé dans le commentaire de bloc | ✅ `<!-- wp:accordion {"foldmark":{"type":"faq"}} -->`, blocs valides |
| Retour FAQ → Accordéon puis Accordéon → FAQ | ✅ via la variation `foldmark/accordion` ; laisse `{"foldmark":null}` dans le commentaire (sans effet) |
| Plugin désactivé : la FAQ reste valide dans l'éditeur | ✅ 13 blocs, 0 invalide |

Non vérifié : une icône SVG personnalisée pour une variation déclarée en PHP
(sinon, dashicon `editor-help`).

## API publique (v1)

| Élément | Rôle |
|---|---|
| filtre `foldmark_output_enabled` | `bool` : `false` coupe la sortie (les données restent calculées) |
| filtre `foldmark_graph` | La liste des nœuds avant encodage ; une liste vide supprime la sortie |
| filtre `foldmark_answer_allowed_html` | La liste `wp_kses` des balises conservées dans les réponses |
| filtre `foldmark_conflicting_blocks` | Les noms de blocs dont la présence désactive la sortie |
| fonction `foldmark_get_graph()` | Les nœuds de la page courante, disponibles dès `wp` (avant `wp_head`), pour un graphe tiers |

## Laissé au constructeur

- L'organisation des classes dans `includes/`.
- Le titre, la description et l'icône de la variation (anglais, traduisible).
- Le hook et la priorité d'analyse, tant que les données sont prêtes avant
  `wp_head`.
- La normalisation utilisée pour la déduplication.
- Les visuels `.wordpress-org/` et les captures du readme.
- La CI (`ci.yml`, `deploy.yml`) calquée sur Tablefold.
