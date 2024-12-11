<?php

namespace App\Flatastic\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController {
    #[Route('/', host: 'flatastic.hatt.style')]
    #[Route('/flatastic.hatt.style', host: 'localhost')]
    public function get(
        LoggerInterface $logger,
    ): Response {
        $text = <<<'ZZZZZZZZZZ'
            <h1>Hassio:</h1>
            <h2>configuration.yml</h2>
            <pre>

            template:
              - sensor:

                # Flatastic chore stats
                - name: Flatmate's chore stats
                    state: "{{ state_attr('sensor.flatastic_flatmates', '%%% user-id %%%')['choreStats'] | int }}"
                    unit_of_measurement: "❤️"

            # deprecated, use UI to configure accordingly.
            camera:
              - platform: generic
                name: Chores
                username: !secret flatastic_username
                password: !secret flatastic_password
                auhentication: basic
                still_image_url: https://flatastic.hatt.style/chores/table/weekly
                scan_interval: 5

            # Shout on flatastic
            rest_command:
              flatastic_shout:
                url: "https://flatastic.hatt.style/shouts/create?shout={{ shout | urlencode }}"
                username: !secret flatastic_username
                password: !secret flatastic_password
            </pre>

            <h2>secrets.yml</h2>
            <pre>
            username: "%%% USERNAME %%%"
            password: "%%% PASSWORD %%%"
            </pre>
            ZZZZZZZZZZ;
        return new Response("{$text}");
    }
}
