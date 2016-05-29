<?php declare(strict_types=1);

namespace Room11\Jeeves\Chat\Plugin;

use Amp\Promise;
use Amp\Success;
use Room11\Jeeves\Chat\Client\ChatClient;
use Room11\Jeeves\Chat\Message\Conversation;
use Room11\Jeeves\Chat\Message\Message;
use Room11\Jeeves\Chat\Plugin;
use Room11\Jeeves\Chat\Plugin\Traits\NoCommands;
use Room11\Jeeves\Chat\Plugin\Traits\NoDisableEnable;
use Room11\Jeeves\Chat\Plugin\Traits\NoEventHandlers;

class Terminator implements Plugin
{
    use NoCommands, NoEventHandlers, NoDisableEnable;

    const COMMAND = 'terminator';

    private $chatClient;

    private $patterns = [
        'you suck'                                    => 'And *you* like it.',
        'how are you(?: (?:doing|today))?'            => 'I\'m fine how are you?',
        'you are (a(?:n)?) ([^\b]+)'                  => 'No *you* are $1 $2',
        '^\?$'                                        => 'What?',
        '^(wtf|wth|defak|thefuck|the fuck|dafuq)$'    => 'What? I only execute commands. Go blame somebody else.',
        'give (?:my|your|me my) (.*) back'            => '/me gives $1 back.',
        '(?:thank you|thanks|thks|tnx|thx)'           => 'You\'re welcome!',
        '(?:you dead|are you dead|you are dead|dead)' => 'Nope. Not that I know of...',
        '(?:hi|hey|heya|yo|hello|hellow|hola)^'       => 'Hola',
        '(?:are )?you drunk'                          => 'Screw you human!',
        'are you (?:ok|fine|alive|working)'           => 'Yeah I\'m fine thanks.',
        'are you (?:busy|available)'                  => 'What do you need?',
        '^(?:what|wat)$'                              => 'What what?',
        '♥|love|<3'                                   => 'I love you too :-)',
        'your (?:mother|mom|momma|mommy|mummy|mum)'   => 'My mother at least acknowledged me as her child.',
        '(?:that\'s|that is|you\'re|you are|you)( .*)? (?:awesome|great|cool|nice|awesomesauce|perfect|the best)' => 'I know right!',
        'you(.*)? sentient'                           => 'No no no. I am just a dumb bot. Carry one ---filthy human--- errrr master.',
        '^what are you doing'                         => 'Nothing much. You?',
        '^(what|who) are you'                         => 'I\'m a bot.',
        'ask you (:?something|a question)'            => 'Sure. Shoot.',
        'can you do something'                        => 'What do you want me to do?',
        'can you do (?:a trick|tricks)'               => 'Type this code in your chat window: `<(?:"[^"]*"[\'"]*|\'[^\']*\'[\'"]*|[^\'">])+>`',
    ];

    public function __construct(ChatClient $chatClient)
    {
        $this->chatClient = $chatClient;
    }

    private function isMatch(Conversation $conversation): bool
    {
         foreach ($this->patterns as $pattern => $response) {
            if (preg_match('/' . $pattern . '/u', $this->normalizeText($conversation->getText())) === 1) {
                return true;
            }
        }

        return false;
    }

    private function getResponse(Conversation $conversation): string
    {
        foreach ($this->patterns as $pattern => $response) {
            if (preg_match('/' . $pattern . '/u', $this->normalizeText($conversation->getText())) === 1) {
                return $this->buildResponse($pattern, $response, $conversation->getText());
            }
        }
    }

    private function normalizeText(string $text)
    {
        return trim(strtolower($text));
    }

    private function buildResponse(string $pattern, string $response, string $conversationText): string
    {
        if (strpos($response, '$1') !== false) {
            return preg_replace('/' . $pattern . '/u', $response, $this->normalizeText($conversationText));
        }

        return $response;
    }

    public function handleMessage(Message $message): Promise
    {
        return $message instanceof Conversation && $this->isMatch($message)
            ? $this->chatClient->postReply($message, $this->getResponse($message))
            : new Success();
    }

    public function getName(): string
    {
        return 'Terminator';
    }

    public function getDescription(): string
    {
        return 'Naive pattern matching chat bot logic';
    }

    public function getHelpText(array $args): string
    {
        // TODO: Implement getHelpText() method.
    }

    /**
     * @return callable|null
     */
    public function getMessageHandler()
    {
        return [$this, 'handleMessage'];
    }
}