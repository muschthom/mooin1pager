define(['jquery', 'core/ajax', 'core/notification', 'format_mooin1pager/ildhvp4'], function ($, Ajax, Notification, ILD) {
    function updateProgressBar(courseId) {
        setTimeout(() => {
            Ajax.call([{
                methodname: 'format_mooin1pager_execute',
                args: { courseid: courseId }
            }])[0]
                .then(result => {
                    console.log("updateProgressBar AJAX Call Result:", result);
                    const curr = result.courseprogress;

                    const container = document.querySelector('.progress-container');
                    if (container) {
                        const innerBar = container.querySelector('.progressbar-inner');
                        const percentSpan = container.querySelector('.font-weight-bold');

                        if (innerBar) {
                            innerBar.style.width = `${curr}%`;
                        }
                        if (percentSpan) {
                            percentSpan.textContent = `${curr}%`;
                        }
                    }
                })
                .catch(error => {
                    Notification.exception(error);
                });
        }, 500);
    }
    return {
        updateProgressBar: updateProgressBar,
    };
});