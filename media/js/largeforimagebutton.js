/**
 * @package    quantummanager
 * @author     Dmitry Tsymbal <cymbal@delo-design.ru>
 * @copyright  Copyright © 2019 Delo Design & NorrNext. All rights reserved.
 * @license    GNU General Public License version 3 or later; see license.txt
 * @link       https://www.norrnext.com
 */

document.addEventListener('DOMContentLoaded', function () {

    let buttonsModal = document.querySelectorAll('.modal-button');

    for (let i=0;i<buttonsModal.length;i++) {
        buttonsModal[i].addEventListener('click', function () {
            let maxTime = 5000;
            let currentTime = 0;
            let title = this.getAttribute('title');
            console.log(window.QuantumwindowPluginMediaLang.label, title);
            if(window.QuantumwindowPluginMediaLang.label === title) {
                let waitModal = setInterval(function () {
                    if(currentTime>maxTime) {
                        clearInterval(waitModal);
                    }

                    if(document.querySelector('#sbox-window') !== null) {
                        document.querySelector('#sbox-window').classList.add('quantummanager-modal-sbox-window');
                        clearInterval(waitModal);
                    }

                    currentTime += 200;
                }, 100);
            }
        });
    }

});