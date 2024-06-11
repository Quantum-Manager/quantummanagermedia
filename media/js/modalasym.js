document.addEventListener('DOMContentLoaded', function () {

    window.acym_editorWysidJoomla.setInsertMediaJoomlaWYSID = function (e, t) {
        console.log(e, t);

        jQuery("#acym__wysid__modal__joomla-image__ui__actions__cancel")
            .off("click")
            .on("click", function () {
                acym_editorWysidJoomla.cancelMediaSelection(t);
            });

        jQuery("#acym__wysid__modal__joomla-image__ui__actions__select")
            .off("click")
            .on("click", function () {
                let root = ACYM_ROOT_URI;
                let content = e.contents();
                let items = [];
                let frame = e.get()[0];

                setTimeout(function () {
                    let fm = frame.contentWindow.QuantummanagerLists[0];
                    let files = fm.Quantumviewfiles.getSelectFiles();

                    frame.contentWindow.QuantumUtils.ajaxGet(
                        frame.contentWindow.QuantumUtils.getFullUrl("index.php?option=com_quantummanager&task=quantumviewfiles.getParsePath&path=" +
                            encodeURIComponent(fm.Quantumviewfiles.path) + '&scope=' +
                            fm.data.scope + '&v=' + frame.contentWindow.QuantumUtils.randomInteger(111111, 999999))
                    ).done(function (response) {
                        response = JSON.parse(response);

                        if (response.path !== undefined) {

                            for(let i = 0; i < files.length; i++)
                            {
                                items.push(root + response.path + '/' + files[i].getAttribute('data-fullname'));
                            }

                            let alt = '';
                            let title = '';
                            let caption = '';

                            window.acym_editorWysidJoomla.validateMediaSelection(t, items, alt, title, caption);
                        }
                    });

                }, 50);

            });
    }

});
