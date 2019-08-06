<h1>Bot Template Framework</h1>

<a href="https://packagist.org/packages/adellantado/bot-template-framework"><image src="https://img.shields.io/packagist/v/adellantado/bot-template-framework.svg"/></a>
<a href="https://packagist.org/packages/adellantado/bot-template-framework"><image src="https://img.shields.io/packagist/dt/adellantado/bot-template-framework.svg"/></a>
	
Allows to simplify chatbot development using Botman. 
With one scenario file you can make your bot working.

You can find downloadable chatbot example [here](https://github.com/adellantado/bot-template-framework-sample).

E.g. Simple Telegram Hello World chatbot:

        {
            "name": "Hello World Chatbot",
             "fallback": {
                "name": "Hello Block",
                "type": "block"
             },
             "blocks": [
                {
                    "name": "Hello Block",
                    "type": "text",
                    "content": "Hello World!",
                    "template": "Hi;Hello;What's up;Good day;/start"
                }
             ], 
             "drivers": [
                {
                    "name": "Telegram",
                    "token": "590000000:AAHp5XAGrpIyZynnIjcLKJSwUpPu0b1FXEY"
                }
             ]
        }

<h2>Usage</h2>

1. Install package with composer:

        composer require adellantado/bot-template-framework
       
2. Add your chatbot scenario template.json file, for example, to storage/app

3. Add drivers to config:

        $this->app->singleton('botman', function ($app) {
            $config = TemplateEngine::getConfig(file_get_contents(storage_path('app/template.json')));
            return BotManFactory::create($config);
        });

4. Call listen() to template engine before Botman:

        $botman = resolve('botman');
        
        $templateEngine = new TemplateEngine(file_get_contents(storage_path('app/template.json')), $botman);
        $templateEngine->listen();
        
        // Start listening
        $botman->listen();

<h2>Scenario file structure</h2>

        // template.json
        
        {
            "name": "Chatbot Name",
            "fallback": "This is fallback message",
            "blocks": [
                {
                    "name": "Simple Text Block",
                    "type": "text",
                    "content": "Hi, this is simple text message"
                },
                {
                    ...
                }
            ],
            "drivers": [
                {
                    "name": "Telegram",
                    "token": "<your-telegram-token>"
                },
                {
                    ...
                }
            ]
        }

   With blocks you can describe action and data to send, like send text or image, request location from user or 
   ask to answer question
   
   Within drivers you set platforms which you want your chatbot working on, like Facebook or Telegram

<h2>Blocks</h2>

There are 21 types of block

        text, image, menu, audio, video, file, location, attachment, carousel, list, request, ask, intent, if, method, extend, idle, save, random, payload, validate

Every block extends abstract block, which has next properties:

        {
            "name": "Block Name",
            "type": "idle",
            "template": "Image;Show image;Want to see an image",
            "typing": "1s",
            "drivers": "any;!telegram",
            "locale": "en",
            "options": {"someProperty": "someValue"}
            "next": "Next Block Name"
        },


   `name` - (required) name of the block, uses to identify blocks;<br>
   `type` - (required) type of the block (e.g. image, text ..);<br>
   `template` - (optional) identify key phrases which chatbot react to (see $botman->hear());<br>
   `typing` - (optional) shows typing effect in chat before running the block;<br>
   `drivers` - (optional) exclude/include block from execution for some drivers (e.g. 'any' or '*' - runs for all drivers,
        'facebook;telegram' - runs for telegram and facebook, 'any;!telegram' - runs for any driver, except telegram);<br>
   `locale` - (optional) assigns block to particular locale, works like namespaces. e.g. you describe blocks with 
        locale 'en' and then copy them translated with locale 'ge';<br>
   `options` - (optional) sets some driver specific properties;<br>
   `next` - (optional) name of the next block to execute in chain



<h3>Text Block</h3>

   Send simple text response

       {
            "name": "Greetings",
            "type": "text",
            "content": "Hi! Nice to meet you {{user.firstName}};Hi there, {{user.firstName}}",
            "template": "Hello;Hi;Good day",
            "typing": "1s"
       }

   note: learn about variables
    
<h3>Image Block</h3>

   Draw image with description and buttons or without them

        {
            "name": "Logo",
            "type": "image",
            "content": {
                "text": "This is the logo;Our logo is following",
                "url": "https://logo.com/logo.jpg",
                "buttons": {
                    "Callback": "Learn More"
                }
            },
            "template": "Show me the logo"
        }
       
   `url` - (required) image url;<br>
   `text` - (optional) image description;<br>
   `buttons` - (optional) adds buttons under image;
   
   note: learn about menu block

<h3>Menu Block</h3>

   1.Show buttons

        {
            "name": "Menu Block",
            "type": "menu",
            "content": {
                "text": "This is a simple menu; This is a menu",
                "buttons": [
                    {"Callback": "Learn More"},
                    {
                        "https://website.com/": "Visit Website", 
                        "Ask Support": "Ask Support"
                    }
                ]
            } 
        }
    	
   note: buttons may vary dramatically from driver to driver (learn more about in official docs of the platform)
        
   E.g. Telegram:
        
        Format #1
         "buttons": [ 
            {                                                     This is a simple menu
                "https://website.com/": "Visit Website",   ==>    ------------------------------------
                "Ask Support": "Ask Support"                      |   Visit Website  |  Ask Support  |
            }                                                     ------------------------------------
         ]                                                        
                                                                  
        Format #2
         "buttons": [ 
            {                                                     This is a simple menu
                "https://website.com/": "Visit Website"   ==>     ---------------------
            },{                                                   |   Visit Website   |
                "Ask Support": "Ask Support"                      ---------------------
            }                                                     |    Ask Support    |
         ]                                                        ---------------------
                                                                
   E.g. Facebook - has 3 only buttons in one menu.
   
   2.Show quick buttons

        {
            "name": "Quick Menu Block",
            "type": "menu",
            "mode": "quick",
            "content": {
                "text": "This is a quick menu",
                "buttons": [
                    {
                        "https://website.com/": "Visit Website", 
                        "Ask Support": "Ask Support"
                    }
                ]    
            } 
        }   
    
<h3>Audio, Video and File Blocks</h3>

   Drop video, audio and file directly to the chat

        {
            "name": "File Block",
            "type": "file",
            "content": {
                "text": "Download the file",
                "url": "https://sample.com/doc.pdf"
            } 
        }

   `url` - (required) file, video or audio link;<br>
   `text` - (optional) description;

<h3>Location Block</h3>

   Request location from the user
   
        {
            "name": "Location Test",
            "type": "location",
            "content": "Please, share your location by clicking button below;Send your location",
            "template": "share location",
            "result": {
                "save": "{{location}}"
            }
        }
		
   `content` - (required) description;<br>
   `result.save` - (required) save data in json {latitude:.., longitude: ..} to variable;
   
   note: learn about variables

<h3>Attachment Block</h3>

   Request image, video, audio or file from the user
   
        {
            "name": "Attachment Test",
            "type": "attachment",
            "mode": "image",
            "content": "Please, make a photo to verify your identity",
            "result": {
                "save": "{{photo}}"
            }
        }
		
   `mode` - (optional) could be image, video, file, audio, if mode not provided - file by default;
   
<h3>Carousel and List Blocks</h3>

   Draw carousel or list of components
   
        {
            "name": "List Test",
            "type": "list",
            "content": [
                {
                    "url": "https://image.com/img1.jpg",
                    "title": "Component #1",
                    "description": "This is component #3"
                },
                {
                    "url": "https://image.com/img2.jpg",
                    "title": "Component #2",
                    "description": "This is component #3"
                },
                {
                    "url": "https://image.com/img3.jpg",
                    "title": "Component #3",
                    "description": "This is component #3",
                    "buttons": {
                        "example btn": "Example Button"
                    }
                }
            ],
        }
		
   note: some platforms doesn't support list or carousel components natively
   
<h3>Request Block</h3>

   Make a custom GET/POST request 

        {
            "name": "Tell a joke",
            "type": "request",
            "method": "GET",
            "url": "http://api.icndb.com/jokes/random",
            "result": {
                "field": "value.joke",
                "save": "{{joke}}"
            },
            "template": "Tell a joke;Joke;Do you know some jokes?"
        }
		
   `result.field` - (optional) read the data from the result;<br>
   `result.save` - (optional) save result to variable;
   
   note: learn about variables
   
<h3>Ask Block</h3>

   Ask a question and wait for user answer

        {
            "name": "Ask Phone",
            "type": "ask",
            "content": "Can you left us your phone to contant you only in case of urgency?",
            "validate": "number",
            "errorMsg": "This is custom error message occurs if validation hasn't been passed",
            "skip": "pause;skip",
            "stop": "stop;off",
            "result": {
                "prompt": "yes;no"
            },
            "next": {
                "yes": "Type Phone Block",
                "no": "Ask Email Block",
                "fallback": "Ask Email Block"
            }
        }
	
   `validate` - (optional) validate user input, doesn't save variable and repeats question when validation isn't passed.
        Possible values: `number` (validates integer), `email` (sends quick button for Facebook and validates email), 
        `url`, `phone` (sends quick button for Telegram and Facebook), `image`, `file`, `video`, `audio`, 
        `location` (sends quick button for Telegram and Facebook), `confirm` (requires two times input), 
        `/^[0-9]*$/` (any regexp, similar to this one);<br>
   `errorMsg` - (optional) validation error message;<br>
   `skip`,`stop` - (optional) pause/stop conversation key phrases;<br>
   `result.prompt` - (optional) shows quick buttons;<br>
   `next.<user answer>` - (optional) depends on user answer, run next block 
        ('fallback' - reserved for any answer which are not in the list).
        
   note: Learn more about results<br>
   note: You need to set up persistent cache (like Redis), learn more on Botman website
   
   
<h3>Intent Block</h3>

        {
            "name": "AlexaTest",
            "provider": "alexa",
            "type": "intent",
            "template": "BeverageIntent",
            "content": "well done; cool",
            "result": {
                "field": "beverage",
                "save": "{{user_beverage}}"
            },
            "next": {
                "coffee": "Coffee Card Block",
                "tea": "Tea Card Block",
                "default": "Repeat Question Block"
            }
        }
		
   `provider` - (required) could be 'alexa', 'wit' or 'dialogflow';<br>
   `template` - (required) intent name for alexa and wit, action name for dialogflow;<br>
   `content` - (required (alexa) | optional (dialogflow) | optional (wit)) answer into the chat;<br>
   `result.field` - (optional) entity or slot name;<br>
   `result.save` - (optional) saves entity or slot value;<br>
   `next.<entity_value>` - (optional) triggers next block by entity or slot value;
   
   note: you should use amazon alexa console, wit or dialogflow console to have 
        this block running;<br>
        
   note: for dialogflow v2, official php library required - google/cloud-dialogflow;<br>
   
   note: to call the block after dialogflow executes, add next payload in dialogflow console - {"next": "MyNextBlock"}
    
<h3>If Block</h3>
    
        {
            "name": "Comparison Test",
            "type": "if",
            "next": [
                ["{{var}}", "==", "1", "Block 1"],
                ["{{var}}", "<", "1", "Block 2"],
                ["{{var}}", ">", "1", "Block 3"],
            ]
        }
        
   E.g. Simply calls "Block 1" when `{{var}} == 1`, calls "Block 2" when `{{var}} < 1` and "Block 3" when `{{var}} > 1`.
   
   Supported operators: `==`, `!=`, `>`, `>=`, `<`, `<=`
    
<h3>Method Block</h3>

   Simply call method from your own strategy

        {
            "name": "Test method",
            "type": "method",
            "method": "myMethod"
        }
    
   `method` - (required) method name
    
   note: For each driver you should have strategy class in App\Strategies 
         folder with 'myMethod' function
         
         namespace App\Strategies;
         use BotTemplateFramework\Strategies\Strategy;
         
         class Telegram extends Strategy {
         
            function myMethod() {
                $this->bot->reply('This is my method replies');
            }
         }

<h3>Extend Block</h3>

   Simply overrides properties of the parent block. Could be very helpful when you need to make very similar block with small changes.

        {
            "name": "Oysters Extended",
            "type": "extend",
            "base": "Oysters Block",
            "content": {
                "url": "https://upload.wikimedia.org/wikipedia/commons/thumb/3/37/Oysters_p1040741.jpg/330px-Oysters_p1040741.jpg"
            }
        },
        {
            "name": "Oysters Block",
            "type": "image",
            "template": "Oysters",
            "content": {
                "url": "https://upload.wikimedia.org/wikipedia/commons/thumb/b/b0/Crassostrea_gigas_p1040848.jpg/450px-Crassostrea_gigas_p1040848.jpg"
            }
        }

   `base` - (required) Name of the parent block<br>

   E.g. Extends "Oysters Block" overriding `content` field
   
   note: do not extend blocks - `intent` and `ask`;<br>
   note: extend only fields of 1st level of nesting (like `type`, `content`, `next`, but impossible to extend only `url` (2nd level of nesting) field from image content);

<h3>Idle Block</h3>

   Simply does nothing, but can be used to call next block

       {
            "name": "Does Nothing",
            "type": "idle",
            "template": "call idle",
            "typing": "1s",
            "next": "Next Block"
       }    
       
       
<h3>Save Block</h3>

   Simply saves some value (including existing variable value) to some variable

       {
            "name": "Saves to Variable",
            "type": "save",
            "value": "123",
            "variable": "{{someVar}}"
       }   

<h3>Random Block</h3>
    
        {
            "name": "Random Block",
            "type": "random",
            "next": [
                ["20%", "Block 1"],
                ["30%": "Block 2"],
                ["40%": "Block 3"]
            ]
        }
        
<h3>Validate Block</h3>
    
   Validates variable and executes block with 'true' key in case of success

        {
            "name": "Validate Block",
            "type": "validate",
            "validate": "number",
            "variable": "{{myVar}}",
            "next": {
                "true": "Block 1",
                "false": "Block 2"
            }
        }        
        
   `variable` - (required) name of variable;<br>
   `validate` - (required) Possible values: `number` (validates integer), `email` (sends quick button for Facebook and validates email), 
                                   `url`, `phone` (sends quick button for Telegram and Facebook), `image`, `file`, `video`, `audio`, 
                                   `location` (sends quick button for Telegram and Facebook), `/^[0-9]*$/` (any regexp, similar to this one);
    
<h3>Payload Block</h3>

   Send a payload to a messenger

        {
            "name": "Test payload",
            "type": "payload",
            "payload": {
                "text": "This is an inline keyboard example",
                "reply_markup": {
                    "inline_keyboard": [
                        [
                            {
                                "text": "Button1",
                                "callback_data": "callback1"
                            },
                            {
                                "text": "Button2",
                                "callback_data": "callback2"
                            }                                          
                        ]
                    ]
                }
            },
            "drivers": "telegram"
        }
    
   `payload` - (required) payload to send;<br>
   `drivers` - (required) it's essential to set exact driver, because a payload is unique for each of them
    
   note: study appropriate messenger's API documentation

<h2>Drivers</h2>

   Before using driver in here, first you need to install proper driver for Botman.
   Available drivers are next:
   
        Facebook, Telegram, Skype, Dialogflow, Alexa, Viber, Web, Chatbase, Wit
   
   note: Because BotMan doesn't ship with Viber driver, you need to run
   
        composer require adellantado/botman-viber-driver,
        
   note: Because BotMan doesn't ship with Chatbase, you need to run
       
        composer require bhavyanshu/chatbase-php   
   
   Example:
    
        "drivers": [
            {
                "name": "Dialogflow",
                "token": "b71dd842a2eb43434f4fg5eee55"
            },
            {
                "name": "Web",
                "token": "web"
            },
            {
                "name": "Chatbase",
                "token": "b71dd-842a-2eb4-3434-fw32e3233"
            },
            {
                "name": "Facebook",
                "app_secret": "FACEBOOK_APP_SECRET",
                "token": "FACEBOOK_TOKEN",
                "verification": "FACEBOOK_VERIFICATION",
                "config": "true",
                "events": {
                    "delivery": "BlockExecOnDeliveryEvent",
                    "read": "BlockExecOnReadEvent"
                }
            }
        ]

   `name` - (required) Name of the driver<br>
   `token` - (require|optional) token for telegram, viber, dialogflow, chatbase, web(optional, default token is 'web').
           Fields: verification, token, app_secret - for facebook; 
           app_id, app_key - for skype;
           project_id, key_path, version=2 - for dialogflow v2 api.<br>
   `config` - (optional) shows that fields should be read from env()<br>
   `events` - (optional) blocks triggers by event. 
           E.g. on "delivery" event in facebook, trigger block with
           name "BlockExecOnDeliveryEvent". See events in Botman.
   

<h2>Variables</h2>

Variables're saving to Botman userStorage(), so be sure to pass proper
    storage to Botman instance.
    
        BotManFactory::create($config, null, null, new FileStorage(__DIR__));

<h3>Using variables</h3>

Use variables with figure brackets

        {{my_variable}}

Save variables with 'result.save' field with request, ask, intent blocks. 
And by using special save block.

<h3>Predefined variables</h3>
 - {{user.id}}<br>
 - {{user.firstName}}<br>
 - {{user.lastName}}<br>
 - {{bot.name}}<br>
 - {{bot.driver}}<br>
 - {{message}} (this is the last user's sent message)

<h2>Results</h2>

   There are 5 blocks which returns result: `location`, `attachment`, `request`, `ask`, `intent`
    
   For each you can apply 
   
        "result": {
            "save": "{{my_variable}}"
        },
	    
   Three of them (`request`, `ask`, `intent`) could have `next` field impacted depends on result value:
   
   E.g. In this example triggers block `Type Phone Block` then result is `yes` string value,
   triggers `Ask Email Block` - when result is `no` and when neither `yes` nor `no`:
   
        "next": {
            "yes": "Type Phone Block",
            "no": "Ask Email Block",
            "fallback": "Ask Email Block"
        }
    
   note: for the `intent` block result is an entity value (dialogflow) or a slot value (alexa), which name set with `field` field
   
<h3>Ask Result</h3>

   Use `prompt` field to add quick buttons and simplify reply for user, like in the example below:
    
        {
            "name": "Ask Phone",
            "type": "ask",
            "content": "Can you left us your phone to contant you only in case of urgency?",
            "result": {
                "prompt": "yes;no"
            },
            "next": {
                "yes": "Type Phone Block",
                "no": "Ask Email Block",
                "fallback": "Ask Email Block"
            }
        }
   
<h3>Request Result</h3>

   Use `field` field to quickly pull data from json response, like here:

        {
            "name": "Tell a joke",
            "type": "request",
            "method": "GET",
            "url": "http://api.icndb.com/jokes/random",
            "result": {
                "field": "value.joke",
                "save": "{{joke}}"
            },
            "template": "Tell a joke;Joke;Do you know some jokes?"
        }
    

   Response looks like this:
    
         { 
            "type": "success", 
            "value": {
                "id": 495, 
                "joke": "Chuck Norris doesn't needs try-catch, exceptions are too afraid to raise.", 
                "categories": ["nerdy"] 
            } 
        }

<h2>Fallback</h2>

   Fallback might be set in 3 different formats:
   
   1.text
           
        "fallback": "This is default reply messsage"

   2.dialogflow
       
        "fallback": {
            "type": "dialogflow",
            "default": "This is default reply message when no answer from Dialogflow"
        }
            
   3.block
   
        "fallback": {
            "name": "Hello Block",
            "type": "block"
        }

<h2>Builder</h2>

   Using builder is a straight-forward, below is an example of simple chatbot:

        $template = (new Template('Beedevs Chatbot'))
            ->addDrivers([
                new TelegramDriver('123123wefwef:wefonwewerwerwerw')
            ])
            ->addFallbackMessage('This is default message')
            ->addBlocks([

                (new TextBlock())
                    ->text('Hi! Welcome to beedevs chatbot')
                    ->template([
                        'Hello',
                        'Hi',
                        'What\'s up',
                        'Good day'
                    ])->typing(1)
                    ->next(
                        $about = (new ImageBlock())
                            ->url('https://pbs.twimg.com/profile_images/799239637684355072/SGIDpffc_400x400.jpg')
                            ->buttons([
                                (new Button('Visit'))->url("https://beedevs.com")
                            ])
                            ->text('Beedevs is a chatbot development studio. Want to know more? Visit our website!')
                            ->template([
                                'About'
                            ])
                    ),

                $about,

                (new RequestBlock())
                    ->url('http://api.icndb.com/jokes/random')
                    ->method('GET')
                    ->result((new RequestResult())
                        ->field(['value', 'joke'])
                        ->save('{{joke}}')
                    )->template([
                        'Tell a joke',
                        'Joke',
                        'Do you know some jokes?'
                    ])
                    ->next(
                        $joke = (new TextBlock())
                            ->text('{{joke}}')
                            ->typing(1)
                    ),

                $joke,

                (new MenuBlock())
                    ->text('Menu')
                    ->buttons([
                        (new Button('Website'))->url("https://beedevs.com"),
                        (new Button('About'))->callback('About')
                    ])
                    ->template([
                        'Show menu',
                        'Menu',
                        'Main menu'
                    ]),

                (new ListBlock())->items([
                    (new ListItem('ListItem1', 'https://static.addtoany.com/images/dracaena-cinnabari.jpg'))
                        ->buttons([
                            (new Button('About'))->callback('About')
                        ])
                        ->description('National Park'),
                    (new ListItem('ListItem2', 'https://cloud.google.com/blog/big-data/2016/12/images/148114735559140/image-classification-1.png'))
                        ->buttons([
                            (new Button('About'))->callback('About')
                        ])
                        ->description('Sunflower fields at summer time')
                ])->template([
                    'List',
                    'Show list'
                ])

            ]);
            
   And then just insert your `$template` into engine, like that:
   
        $templateEngine = new TemplateEngine($template, $botman);
        $templateEngine->listen();
                
        // Start listening
        $botman->listen();
        
  Inside, engine, just converts it to an array similar to json you already used to, but with builder your 
  abilities leveling up with convenience and speed of development.
