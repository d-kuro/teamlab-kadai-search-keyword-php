<!DOCTYPE html>
<html xmlns:th="http://www.thymeleaf.org">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <title>チームラボ選考課題</title>
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous" />
  <style>
    body {
      padding-top: 40px;
      padding-bottom: 40px;
      background-color: #eee;
    }
  </style>
</head>
<body>
<div class="container">
  <hr />
  <section class="row text-center placeholders">
    <div class="col-2 col-sm-5 placeholder">
      <h2><span>{{ $searchTime }}</span> ミリ秒</h2>
      <div class="text-muted">速度</div>
    </div>
    <div class="col-2 col-sm-5 placeholder">
      <h2><span>{{ $result }}</span> 件</h2>
      <div class="text-muted">ヒット数</div>
    </div>
  </section>
  <hr />
  <div class="table-responsive" id="myTableId">
    <table class="table table-striped">
      <thead>
      <tr>
        <th>ユーザID</th>
        <th>ユーザ名</th>
        <th>ページID</th>
        <th>ページタイトル</th>
        <th>閲覧数</th>
      </tr>
      </thead>
      <tbody>
      @foreach($userPages as $userPage)
      <tr>
        <td>{{ isset($userPage['user_id']) ? $userPage['user_id'] : '' }}</td>
        <td>{{ isset($userPage['user_name']) ? $userPage['user_name'] : '' }}</td>
        <td>{{ isset($userPage['page_id']) ? $userPage['page_id'] : '' }}</td>
        <td>{{ isset($userPage['page_title']) ? $userPage['page_title'] : '' }}</td>
        <td>{{ isset($userPage['view_count']) ? $userPage['view_count'] : '' }}</td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
</body>
</html>