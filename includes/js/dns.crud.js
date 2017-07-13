jQuery.noConflict();

var DNSEntries = {};
var AddDNSRecordModel = {};
var EditDNSRecordModel = {};
var SystemCheckResult = {};
var DPT_DNS = {
  TableScope:null
};
var URL = {
  AJAX:ajaxurl
};
var MessageBox = null;

(function($) {
  $(function() {

    function MessageBoxClass() {
      this.Prompt = function(message) {
        var messageBoxDOM = $("#MessageBox");
        alert = '\
<div class="alert alert-danger" role="alert">\
<button type="button" class="close" data-dismiss="alert" aria-label="Close">\
<span aria-hidden="true">&times;</span>\
</button>\
<p>'+message+'</p>\
</div>';
        $(messageBoxDOM).append(alert)
      }
    }
    MessageBox = new MessageBoxClass();

    $('#DNSManagerListControl').each(function() {
      var TableDOM = $(this);
      function UpdateDNSEntriesCallback(result) {
        var $scope = $(TableDOM).scope();
        DPT_DNS.TableScope = $scope;
        $scope.$apply(function() {
          DNSEntries = result.records;
          $scope.DNSEntries = DNSEntries;
        });
      }
      $.ajax({
        dataType: "json"
        , url: URL.AJAX
        , data: {
          action: "dpt-dnsmanager"
          , sub_action: "GetRecords"
        }
        , success: UpdateDNSEntriesCallback
      });
    });

    $('#DeleteDNSFile').click(function() {
      var TableDOM = $('#DNSManagerListControl');
      function ReloadDNSEntriesCallback(result) {
        var $scope = $(TableDOM).scope();
        $scope.$apply(function() {
          DNSEntries = result.records;
          $scope.DNSEntries = DNSEntries;
        });
      }
      $.ajax({
        dataType: "json"
        , url: URL.AJAX
        , data: {
          action: "dpt-dnsmanager"
          , sub_action: "DeleteDNSFile"
        }
        , success: ReloadDNSEntriesCallback
      });
    });

    $('#SaveAndRestart').click(function() {
      var TableDOM = $('#DNSManagerListControl');
      function ReloadDNSEntriesCallback(result) {
        var $scope = $(TableDOM).scope();
        $scope.$apply(function() {
          DNSEntries = result.records;
          $scope.DNSEntries = DNSEntries;

        });
      }
      $.ajax({
        dataType: "json"
        , url: URL.AJAX
        , data: {
          action: "dpt-dnsmanager"
          , sub_action: "SaveAndRestart"
        }
        , success: ReloadDNSEntriesCallback
      });
    });

    $('#SaveToFile').click(function() {
      var TableDOM = $('#DNSManagerListControl');
      function ReloadDNSEntriesCallback(result) {
        var $scope = $(TableDOM).scope();
        $scope.$apply(function() {
          DNSEntries = result.records;
          $scope.DNSEntries = DNSEntries;

        });
      }
      $.ajax({
        dataType: "json"
        , url: URL.AJAX
        , data: {
          action: "dpt-dnsmanager"
          , sub_action: "SaveToFile"
        }
        , success: ReloadDNSEntriesCallback
      });
    });

    $('#Reload').click(function() {
      var TableDOM = $('#DNSManagerListControl');
      function ReloadDNSEntriesCallback(result) {
        var $scope = $(TableDOM).scope();
        $scope.$apply(function() {
          DNSEntries = result.records;
          $scope.DNSEntries = DNSEntries;
        });
      }
      $.ajax({
        dataType: "json"
        , url: URL.AJAX
        , data: {
          action: "dpt-dnsmanager"
          , sub_action: "ReloadDNSFile"
        }
        , success: ReloadDNSEntriesCallback
      });
    });

    $('#ExportButton').click(function() {
      var url = URL.AJAX+"?action=dpt-dnsmanager&sub_action=DownloadDNSFile";
      location.href = url;
    });

    //Enable ViewBindFile
    $('#ViewBindFile').each(function() {
      var modalDOM = $(this);
      function UpdateModalCallback(result) {
        $(".modal-body", modalDOM).html("<pre>"+result.HTML+"</pre>");
      };
      modalDOM.on('show.bs.modal', function(event) {
        $.ajax({
          dataType: "json"
          , url: URL.AJAX
          , data: {
            action: "dpt-dnsmanager"
            , sub_action: "ViewDNSFile"
          }
          , success: UpdateModalCallback
        });
      });
    });

    function DNSController(DOM) {
      $('#AddDNSRecord, #EditDNSRecord', DOM).each(function() {
        var modalDOM = $(this);
        var formGroup = {};

        //setup edit model to pull from dataset;
        modalDOM.on('show.bs.modal', function(event) {
          var button = $(event.relatedTarget);
          var id = button.data('id');
          EditDNSRecordModel = DNSEntries[id];
          var $scope = $(this).scope();
          $scope.$apply(function() {
            $scope.DNSEntriesIndex = id;
            //tricky way of copying object
            $scope.editui = $.extend({},DNSEntries[id]);
          });
          //add ability to close modal from angularjs
          $scope.CloseModal = function() {
            modalDOM.modal('hide');
          };
          //make sure we force update the ui.
          $("select[name='type']", modalDOM).trigger('change');
        });

        //Place all form controls into a hash for easy access
        $(".modal-body .form-group", modalDOM).each(function() {
          formGroup[$(".form-control", this).attr("name")] = this;
        });

        //Make the 'type' dropdown dynamically change the display of the form
        $("select[name='type']", modalDOM).change(function() {
          var type = $(this).val();
          $(formGroup["destination_ip"]).hide();
          $(formGroup["destination_domain"]).hide();
          $(formGroup["primary_ns"]).hide();
          $(formGroup["domain_email"]).hide();
          $(formGroup["serial"]).hide();
          $(formGroup["refresh"]).hide();
          $(formGroup["retry"]).hide();
          $(formGroup["expire"]).hide();
          $(formGroup["negative_response_ttl"]).hide();
          $(formGroup["priority"]).hide();
          switch(type) {
            case "IN NS":
              $(formGroup["destination_domain"]).show();
              break;
            case "IN CNAME":
              $(formGroup["destination_domain"]).show();
              break;
            case "IN A":
              $(formGroup["destination_ip"]).show();
              break;
            case "IN MX":
              $(formGroup["priority"]).show();
              $(formGroup["destination_domain"]).show();
              break;
            case "IN SOA":
              $(formGroup["primary_ns"]).show();
              $(formGroup["domain_email"]).show();
              $(formGroup["serial"]).show();
              $(formGroup["refresh"]).show();
              $(formGroup["retry"]).show();
              $(formGroup["expire"]).show();
              $(formGroup["negative_response_ttl"]).show();
              break;
            default:
              break;
          }
        });

      });

      //Enable SystemCheck
      $('#SystemCheck', DOM).each(function() {
        var modalDOM = $(this);
        function SystemCheckCallback(result) {
          if(result==0)
            return;
          //$(".modal-body", DOM).html(result.HTML);
          SystemCheckResult = result;
          var details = SystemCheckResult.details;
          SystemCheckResult.details = [];
          for(var index in details) {
            SystemCheckResult.details.push(details[index]);
          }
          var $scope = $('#SystemCheckControl', DOM).scope();
          $scope.$apply(function() {
            $scope.SystemCheckResult = SystemCheckResult;
          });
        };
        modalDOM.on('show.bs.modal', function(event) {
          $.ajax({
            dataType: "json"
            , url: URL.AJAX
            , data: {
              action: "dpt-dnsmanager"
              , sub_action: "SystemCheck"
            }
            , success: SystemCheckCallback
          });
        });
      });

    };
    var dnsController = new DNSController(document);
  });
})(jQuery);

var DNSApp = angular.module('DNSManagerApp', [])
  .filter('trustAsHTML', ['$sce', function($sce) {
    return function(input) {
      return $sce.trustAsHtml(input);
    };
  }]);

DNSApp.controller('CheckSystemControl', ['$scope', '$sce', function($scope, $sce) {
      $scope.SystemCheckResult = SystemCheckResult;
    }]);


DNSApp.controller('DNSManagerListControl', function ($scope) {
  $scope.DNSEntries = DNSEntries;
  $scope.GetDestination = function(DNSEntry) {
    switch(DNSEntry.type) {
      case "IN A":
        return DNSEntry.destination_ip;
        break;
      case "IN CNAME":
      case "IN NS":
        return DNSEntry.destination_domain;
        break;
      case "IN MX":
        return DNSEntry.priority+" "+DNSEntry.destination_domain;
        break;
      case "IN SOA":
        return DNSEntry.primary_ns;
        break;
    }
  };
  $scope.DeleteClicked = function(ID) {
    //ajax Delete
    function DeleteDNSRecordCallback(result) {

    }
    jQuery.ajax({
      dataType: "json"
      , url: URL.AJAX
      , data: {
        action: "dpt-dnsmanager"
        , sub_action: "DeleteRecord"
        , id: ID
      }
      , success: DeleteDNSRecordCallback
    });
    delete($scope.DNSEntries[ID]);
    //$scope.DNSEntries.splice($scope.DNSEntries.indexOf(DNSEntry), 1);
  };
//  DNSEntries = $scope.DNSEntries;
});

DNSApp.controller('AddDNSRecord', ['$scope', function($scope) {
      $scope.master = AddDNSRecordModel;
      var counter = 0;

      $scope.submit = function() {
        function AddDNSRecordCallback(result) {
          if(result.successful) {
            //DNSEntries.push(angular.copy($scope.addui));
            //DNSEntries["angular_"+counter++] = angular.copy($scope.addui);
            DPT_DNS.TableScope.$apply(function() {
              DNSEntries = result.records;
              DPT_DNS.TableScope.DNSEntries = DNSEntries;
            });
          }
          else {
            //Problems Encountered Adding dns record
            MessageBox.Prompt(result.error_msg);
          }
        }
        jQuery.ajax({
          dataType: "json"
          , url: URL.AJAX
          , data: {
            action: "dpt-dnsmanager"
            , sub_action: "AddRecord"
            , form_values: angular.copy($scope.addui)
          }
          , success: AddDNSRecordCallback
        });
        $scope.CloseModal();
      };

      //scope submit defined in jquery area
      //$scope.submit = function() {}

      $scope.reset = function() {
        $scope.addui = angular.copy($scope.master);
      };

      $scope.reset();
    }]);

DNSApp.controller('EditDNSRecord', ['$scope', function($scope) {
      $scope.master = EditDNSRecordModel;

      $scope.submit = function() {
        //ajax Update
        DNSEntries[$scope.DNSEntriesIndex]=angular.copy($scope.editui);
        $scope.editui.id = $scope.DNSEntriesIndex;
        function EditDNSRecordCallback(result) {
          if(result.successful) {
            //DNSEntries.push(angular.copy($scope.addui));
            //DNSEntries["angular_"+counter++] = angular.copy($scope.addui);
            DPT_DNS.TableScope.$apply(function() {
              DNSEntries = result.records;
              DPT_DNS.TableScope.DNSEntries = DNSEntries;
            });
          }
          else {
            //Problems Encountered Adding dns record
            MessageBox.Prompt(result.error_msg);
          }
        }
        jQuery.ajax({
          dataType: "json"
          , url: URL.AJAX
          , data: {
            action: "dpt-dnsmanager"
            , sub_action: "UpdateRecord"
            , form_values: angular.copy($scope.editui)
          }
          , success: EditDNSRecordCallback
        });
        $scope.CloseModal();
      };

      $scope.reset = function() {
        $scope.editui = angular.copy($scope.master);
      };

      $scope.reset();

    }]);
