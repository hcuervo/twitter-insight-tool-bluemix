<!DOCTYPE html>
<html>
<head>
    <title>Twitter Insight Tool</title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/css/bootstrap.min.css">

</head>
<body>
<div class="container">
    <h1 class="text-center">Twitter Insight Tool</h1>
    <form id="frmMain" action="/api/init" method="post">
        <table class="table table-bordered">
            <tr>
                <td>Host : </td>
                <td><input type="text" class="form-control" name="host" value="cdeservice.mybluemix.net" /></td>
            </tr>
            <tr>
                <td>Username : </td>
                <td><input type="text" class="form-control" name="username" /></td>
            </tr>
            <tr>
                <td>Password : </td>
                <td><input type="text" class="form-control" name="password" /></td>
            </tr>
            <tr>
                <td>Query String : </td>
                <td><input type="text" class="form-control" name="q" placeholder="iphone6 blackberry" /></td>
            </tr>
            <tr>
                <td class="text-center">
                    <input type="submit" class="btn btn-block btn-lg btn-primary" value="RUN !" />
                </td>
                <td class="text-center">
                    <button class="btn btn-block btn-lg btn-danger" id="btnStop">STOP !</button>
                </td>
            </tr>
        </table>
    </form>
    <ul class="list-group" id="result">
      <li class="list-group-item">System ready ! :D</li>
    </ul>
</div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/js/bootstrap.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            var frm = $('#frmMain');
            var crawling = false;
            var stopped = false;
            var fails = 0;

            $('#btnStop').click(function(ev) {
                ev.preventDefault();

                if (!stopped) {
                    stopSearch();
                }
            });

            frm.submit(function (ev) {
                ev.preventDefault();

                if (crawling) {
                    $("#result").append('<li class="list-group-item">Can not start because it is crawling ... Just click STOP !</li>');

                    return false;
                }

                $.ajax({
                    type: frm.attr('method'),
                    url: frm.attr('action'),
                    data: frm.serialize(),
                    success: function (data) {
                        $("#result").append('<li class="list-group-item">' + JSON.stringify(data) + '</li>');

                        crawling = true;
                        stopped = false;
                        fails = 0;
                        loadSearch(data);
                    }
                });
            });

            $(document).on('submit', 'form.uploadForm' ,function (ev) {
                ev.preventDefault();

                var frm = $(this);

                $.ajax({
                    type: frm.attr('method'),
                    url: frm.attr('action'),
                    data: frm.serialize(),
                    beforeSend: function (xhr, settings) {
                        $("#result").append('<li class="list-group-item">Uploading ...</li>');
                    },
                    success: function (data) {
                        if ("status" in data && data.status == 1) {
                            $("#result").append('<li class="list-group-item">Uploaded to `/tmp/' + data.file_name + '` !</li>');
                        } else {
                            $("#result").append('<li class="list-group-item">Upload failed !</li>');
                            console.log(data);
                        }
                    }
                });
            });

            function stopSearch() {
                crawling = false;
                stopped = true;

                $("#result").append('<li class="list-group-item">Stopping ... wait to last crawling !</li>');
            }

            function finalizeResult() {
                crawling = false;
                stopped = true;

                $.ajax({
                    type: 'post',
                    url: '/api/finalize_result',
                    success: function (data) {
                        if (data.status == 1) {
                            $("#result").append('<li class="list-group-item"><a href="/download/'+ data.file_name + '" target="_blank">Right Click and select Save As !</a> or You can upload to HDFS via API with below settings.</li>');
                            $("#result").append('<li class="list-group-item"><form class="uploadForm form-inline" method="post" action="/api/upload"><input type="text" name="host" placeholder="Host (without port)" class="form-control" /> <input type="text" placeholder="HDFS User ID" name="userid" class="form-control" /> <input type="text" name="password" placeholder="HDFS Password" class="form-control" /> <input type="submit" class="btn btn-primary" value="Upload" /></form></li>');
                        }
                    }
                });
            }

            function loadSearch(data) {
                var search_data = data;
                if (!crawling || data.status == 0) {
                    if (stopped) {
                        $("#result").append('<li class="list-group-item">Stopped !</li>');
                    }

                    finalizeResult();

                    return false;
                }

                if ("current" in data) {
                    if (data.current >= data.total) {
                        crawling = false;
                        loadSearch(data);
                        return false;
                    }
                }

                $.ajax({
                    type: 'post',
                    url: '/api/search',
                    success: function (data) {
                        $("#result").append('<li class="list-group-item">' + JSON.stringify(data) + '</li>');

                        setTimeout(function() {
                            loadSearch(data);
                        }, 500);
                    },
                    error: function (xhr, error) {
                        console.log(error);
                        fails++;
                        // Try again if fail
                        if (fails <= 3) {
                            $("#result").append('<li class="list-group-item">Failed ! Try again in next 5 seconds</li>');
                            setTimeout(function() {
                                loadSearch(search_data);
                            }, 5000);
                        } else {
                            stopSearch();
                            finalizeResult();
                        }
                    }
                });
            }
        });
    </script>

</body>
</html>