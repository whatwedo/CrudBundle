# Ajax

You can change input fields live while editing with crudbundle.

## Javascript
Add the `ajax.js` file to your template:
```
{% javascripts
    '@whatwedoCrudBundle/Resources/public/js/ajax.js'
%}
<script type="text/javascript" src="{{ asset_url }}"></script>
{% endjavascripts %}
```

## Definition

Add the capability:
```
    public static function getCapabilities()
    {
        return [
            Page::INDEX,
            Page::SHOW,
            Page::DELETE,
            Page::EDIT,
            Page::CREATE,
            Page::AJAXFORM
        ];
    }
```

Choose which property to listen on:
``` 
    public function addAjaxOnChangeListener()
    {
        return [
            'playerOne' => AbstractDefinition::AJAX_LISTEN,
            'playerTwo' => AbstractDefinition::AJAX_LISTEN,
            'winner'    => AbstractDefinition::AJAX
        ];
    }
```

* `AJAX_LISTEN` - when these fields are changed the method `ajaxOnDataChanged` is called
* `AJAX` - these fields do not trigger a `ajaxOnDataChanged` 

Do not use `AJAX_LISTEN` everywhere to prevent a circular event firing. 

Implement `ajaxOnDataChanged`.

The data is passed as an array like that:
```
$data = [
	'playerOne' => 1,
	'playerTwo' => 2,
	'winner'    => ''
];
```
Imagine a match where player #1 competes against player #2. Now the winner can only be one of the two players. So as
soon as the players #1 and #2 are selected, we update the winner select field to only show these two options. 
``` 
public function ajaxOnDataChanged($data)
    {
        $playerIds = [];
        $oldValue = null;
        if (!empty($data['playerOne'])) {
            $playerIds[] = $data['playerOne'];
        }
        if (!empty($data['playerTwo'])) {
            $playerIds[] = $data['playerTwo'];
        }
        if (!empty($data['winner'])) {
            $oldValue = $data['winner'];
        }
        $pRepo = $this->getDoctrine()->getRepository(Player::class);
        $winners = $pRepo
            ->createQueryBuilder('p')
            ->where('p.id in (:ids)')
            ->setParameter('ids', $playerIds)
            ->getQuery()->getResult();
        $winnerValues = ['-' => 'Unentschieden'];
        foreach ($winners as $winner) {
            $winnerValues[$winner->getId()] = $winner->__toString();
        }
        $obj = new \stdClass();
        $obj->data = [
            'winner' => [
                'values' => $winnerValues,
                'value' => $oldValue
            ]
        ];
        return $obj;
    }
```
* `values` - (only when for select fields) Array of available options (key = option-value ; value = option-label)
* `value` - the new value to set. Use `null` for empty. When you used a '-' as key in values, it will represent the null value.  
