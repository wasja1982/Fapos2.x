<p>Пользователь "<a href="mailto:{{ from.email }}">{{ from.name }}</a>" с сайта "<a href="http://{{ fps_server_name }}">{{ site_title }}</a>" написал вам письмо.</p>
<p>Тема письма: {{ mail.subject }}</p>
<p>Текст письма:</p>
<p>----------------------------------------</p>
{{ mail.message }}
<p>----------------------------------------</p>
